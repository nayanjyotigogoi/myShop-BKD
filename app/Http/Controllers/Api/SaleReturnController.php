<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Invoice;
use App\Services\InvoiceService;
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
     * Create a sale return + credit note
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
            $requestQtyTracker = [];

            $saleReturn = SaleReturn::create([
                'sale_id'        => $sale->id,
                'return_date'    => now(),
                'refund_amount'  => 0,
                'refund_method'  => $validated['refund_method'] ?? null,
                'reason'         => $validated['reason'] ?? null,
                'created_by'     => $request->user()->id ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {

                $saleItem = SaleItem::where('id', $itemData['sale_item_id'])
                    ->where('sale_id', $sale->id)
                    ->firstOrFail();

                $alreadyReturned = SaleReturnItem::where('sale_item_id', $saleItem->id)
                    ->sum('quantity');

                $requestQtyTracker[$saleItem->id] =
                    ($requestQtyTracker[$saleItem->id] ?? 0) + $itemData['quantity'];

                $availableQty = $saleItem->quantity - $alreadyReturned;

                if ($requestQtyTracker[$saleItem->id] > $availableQty) {
                    abort(422, 'Return quantity exceeds available quantity');
                }

                $lineTotal = $saleItem->unit_price * $itemData['quantity'];

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id'   => $saleItem->id,
                    'product_id'     => $saleItem->product_id,
                    'quantity'       => $itemData['quantity'],
                    'unit_price'     => $saleItem->unit_price,
                    'line_total'     => $lineTotal,
                ]);

                Product::where('id', $saleItem->product_id)
                    ->increment('current_stock', $itemData['quantity']);

                $refundTotal += $lineTotal;
            }

            $saleReturn->update([
                'refund_amount' => $refundTotal,
            ]);

            /* ================= CREDIT NOTE ================= */

            Invoice::create([
                'invoice_number' => InvoiceService::generate('return'),
                'invoice_type'   => 'return',
                'sale_id'        => $sale->id,
                'sale_return_id' => $saleReturn->id,
                'invoice_date'   => now(),
                'gross_amount'   => $sale->subtotal,
                'discount'       => $sale->discount,
                'refund_amount'  => $refundTotal,
                'net_amount'     => max(0, $sale->total - $refundTotal),
            ]);

            return response()->json([
                'success' => true,
                'refund_amount' => $refundTotal,
                'return' => $saleReturn->load('items.product'),
            ], 201);
        });
    }
}
