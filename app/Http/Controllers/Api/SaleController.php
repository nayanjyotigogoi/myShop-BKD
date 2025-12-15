<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * List sales with summary (gross, refund, net).
     */
    public function index(Request $request)
    {
        $query = Sale::query();

        if ($from = $request->input('from')) {
            $query->whereDate('sale_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('sale_date', '<=', $to);
        }

        $sales = $query
            ->withCount('items')
            ->withSum('items as total_items', 'quantity')
            ->withSum('returns as refund_total', 'refund_amount')
            ->orderBy('sale_date', 'desc')
            ->get();

        // Compute net_total per sale
        $sales->each(function ($sale) {
            $sale->refund_total = (float) ($sale->refund_total ?? 0);
            $sale->net_total = max(0, $sale->total - $sale->refund_total);
        });

        return response()->json($sales);
    }

    /**
     * Store a new sale.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_date'           => 'required|date',
            'bill_number'         => 'nullable|string|max:100',
            'discount'            => 'nullable|numeric|min:0',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $sale = null;

        DB::transaction(function () use (&$sale, $validated) {
            $discount = (float) ($validated['discount'] ?? 0);

            $sale = Sale::create([
                'sale_date'   => $validated['sale_date'],
                'bill_number' => $validated['bill_number'] ?? null,
                'subtotal'    => 0,
                'discount'    => $discount,
                'total'       => 0,
            ]);

            $subtotal = 0;

            foreach ($validated['items'] as $itemData) {
                $product   = Product::findOrFail($itemData['product_id']);
                $quantity  = (int) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];

                if ($product->current_stock < $quantity) {
                    throw new \RuntimeException("Not enough stock for product {$product->id}");
                }

                $lineTotal = $quantity * $unitPrice;
                $subtotal += $lineTotal;

                $unitCost   = (float) $product->buy_price;
                $lineProfit = ($unitPrice - $unitCost) * $quantity;

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $product->id,
                    'mrp'         => (float) $product->sell_price,
                    'quantity'    => $quantity,
                    'unit_price'  => $unitPrice,
                    'line_total'  => $lineTotal,
                    'unit_cost'   => $unitCost,
                    'line_profit' => $lineProfit,
                ]);

                $product->decrement('current_stock', $quantity);
            }

            $sale->update([
                'subtotal' => $subtotal,
                'total'    => max(0, $subtotal - $discount),
            ]);
        });

        $sale->load('items.product');

        return response()->json($sale, Response::HTTP_CREATED);
    }

    /**
     * Show a single sale with returns & net totals.
     */
    public function show(Sale $sale)
    {
        $sale->load([
            'items.product',
            'returns.items.product',
        ]);

        // Remaining qty per item
        $sale->items->each(function ($item) {
            $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)->sum('quantity');
            $item->remaining_qty = max(0, $item->quantity - $returnedQty);
        });

        // Totals
        $refundTotal = $sale->returns()->sum('refund_amount');

        $sale->refund_total = (float) $refundTotal;
        $sale->net_total = max(0, $sale->total - $refundTotal);

        return response()->json($sale);
    }
}
