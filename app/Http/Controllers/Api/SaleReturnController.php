<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    /**
     * List returns for a sale
     */
    public function index(Sale $sale)
    {
        return $sale->returns()
            ->with('items.product')
            ->latest()
            ->get();
    }

    /**
     * Create a sale return
     */
    public function store(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'refund_method' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($sale, $validated, $request) {

            $refundTotal = 0;

            /**
             * 1️⃣ Create Sale Return (initial)
             */
            $saleReturn = SaleReturn::create([
                'sale_id'        => $sale->id,
                'return_date'    => now(),
                'refund_amount'  => 0, // temporary
                'refund_method'  => $validated['refund_method'] ?? null,
                'reason'         => $validated['reason'] ?? null,
                'created_by'     => $request->user()->id,
            ]);

            /**
             * 2️⃣ Process return items
             *     - Prevent over-return
             *     - Prevent duplicate item returns in same request
             */
            $requestQtyTracker = [];

            foreach ($validated['items'] as $itemData) {

                // Ensure sale item belongs to this sale
                $saleItem = SaleItem::where('id', $itemData['sale_item_id'])
                    ->where('sale_id', $sale->id)
                    ->firstOrFail();

                // Already returned qty (previous returns)
                $alreadyReturned = SaleReturnItem::where('sale_item_id', $saleItem->id)
                    ->sum('quantity');

                // Track qty within this request (prevents duplicates)
                $requestQtyTracker[$saleItem->id] =
                    ($requestQtyTracker[$saleItem->id] ?? 0) + $itemData['quantity'];

                $availableQty = $saleItem->quantity - $alreadyReturned;

                if ($requestQtyTracker[$saleItem->id] > $availableQty) {
                    abort(
                        422,
                        "Return quantity exceeds available quantity for product ID {$saleItem->product_id}"
                    );
                }

                $lineTotal = $saleItem->unit_price * $itemData['quantity'];

                /**
                 * Create Sale Return Item
                 */
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id'   => $saleItem->id,
                    'product_id'     => $saleItem->product_id,
                    'quantity'       => $itemData['quantity'],
                    'unit_price'     => $saleItem->unit_price,
                    'line_total'     => $lineTotal,
                ]);

                /**
                 * ✅ STOCK ROLLBACK
                 */
                Product::where('id', $saleItem->product_id)
                    ->increment('current_stock', $itemData['quantity']);

                $refundTotal += $lineTotal;
            }

            /**
             * 3️⃣ Finalize refund amount
             */
            $saleReturn->update([
                'refund_amount' => $refundTotal,
            ]);

            return response()->json([
                'success'        => true,
                'refund_amount'  => $refundTotal,
                'return'         => $saleReturn->load('items.product'),
            ], 201);
        });
    }
}
