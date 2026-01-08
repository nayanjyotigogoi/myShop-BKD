<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * List sales
     */
    public function index(Request $request)
    {
        return Sale::with(['invoices', 'payments', 'customer'])
            ->orderBy('sale_date', 'desc')
            ->get();
    }

    /**
     * Store a sale (POS)
     */
    public function store(Request $request)
    {
        Log::info('SALE RAW REQUEST', $request->all());

        $validated = $request->validate([
            'sale_date'          => 'required|date',
            'discount'           => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'customer_id'        => 'nullable|exists:customers,id',
            'paid_now'           => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|string|max:50',
        ]);

        return DB::transaction(function () use ($request, $validated) {

            $customerId = !empty($validated['customer_id'])
                ? (int) $validated['customer_id']
                : null;

            $discount = (float) ($validated['discount'] ?? 0);
            $paidNow  = (float) $request->input('paid_now', 0);

            Log::info('SALE NORMALIZED VALUES', compact('customerId', 'discount', 'paidNow'));

            /* =====================================================
             * CREATE SALE
             * ===================================================== */
            $sale = Sale::create([
                'sale_date'   => $validated['sale_date'],
                'subtotal'    => 0,
                'discount'    => $discount,
                'total'       => 0,
                'customer_id' => $customerId,

                // ❌ COMMENTED (27.12.2025)
                // payment_status MUST NOT be stored.
                // It is now a derived attribute in Sale model.
                // 'payment_status' => 'unpaid',
            ]);

            /* =====================================================
             * SALE ITEMS + STOCK UPDATE
             * ===================================================== */
            $subtotal = 0;

            foreach ($validated['items'] as $item) {

                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->current_stock < $item['quantity']) {
                    abort(422, 'Insufficient stock for product: ' . $product->name);
                }

                // ✅ COST & PROFIT CALCULATION (VALID – KEEP)
                $unitCost   = (float) $product->buy_price;
                $lineCost   = $unitCost * $item['quantity'];
                $lineTotal  = $item['quantity'] * $item['unit_price'];
                $lineProfit = $lineTotal - $lineCost;

                $subtotal += $lineTotal;

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $product->id,
                    'mrp'         => $product->sell_price,
                    'unit_cost'   => $unitCost,
                    'unit_price'  => $item['unit_price'],
                    'quantity'    => $item['quantity'],
                    'line_total'  => $lineTotal,
                    'line_profit' => $lineProfit,
                ]);

                $product->decrement('current_stock', $item['quantity']);
            }

            /* =====================================================
             * TOTALS
             * ===================================================== */
            $total = max(0, $subtotal - $discount);

            if ($paidNow > $total) {
                abort(422, 'Payment cannot exceed sale total');
            }

            $sale->update([
                'subtotal' => $subtotal,
                'total'    => $total,
            ]);

            /* =====================================================
             * INVOICE
             * ===================================================== */
            Invoice::create([
                'invoice_number' => InvoiceService::generate('sale'),
                'invoice_type'   => 'sale',
                'sale_id'        => $sale->id,
                'invoice_date'   => $sale->sale_date,
                'gross_amount'   => $subtotal,
                'discount'       => $discount,
                'refund_amount'  => 0,
                'net_amount'     => $total,
            ]);

            /* =====================================================
             * PAYMENT AT SALE TIME
             * ===================================================== */
            if ($paidNow > 0) {

                if (!$customerId) {
                    abort(422, 'Customer is required when payment is made');
                }

                Payment::create([
                    'receipt_no'     => Payment::generateReceiptNo(),
                    'sale_id'        => $sale->id,
                    'customer_id'    => $customerId,
                    'amount'         => $paidNow,
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'payment_date'   => now(),
                ]);
            }

            /* =====================================================
             * CUSTOMER DUE (VALID – KEEP)
             * ===================================================== */
            $due = max(0, $total - $paidNow);

            if ($customerId && $due > 0) {
                Customer::where('id', $customerId)
                    ->increment('due_balance', $due);
            }

            /* =====================================================
             * PAYMENT STATUS UPDATE
             * ===================================================== */

            // ❌ COMMENTED (27.12.2025)
            // payment_status is DERIVED from payments & returns.
            // Updating it here causes desync.
            /*
            $sale->update([
                'payment_status' => $paidNow >= $total
                    ? 'paid'
                    : ($paidNow > 0 ? 'partial' : 'unpaid'),
            ]);
            */

            return response()->json(
                $sale->load(['items.product', 'payments', 'customer', 'invoices']),
                Response::HTTP_CREATED
            );
        });
    }

    /**
     * Show sale details
     */
    public function show(Sale $sale)
    {
        $sale->load([
            'items.product',
            'returns.items.product',
            'returns.invoice',
            'returns.payments',
            'payments',
            'customer',
            'invoices',
        ]);

        $sale->items->each(function ($item) {
            $returned = SaleReturnItem::where('sale_item_id', $item->id)->sum('quantity');
            $item->remaining_qty = max(0, $item->quantity - $returned);
        });

        return response()->json($sale);
    }
}
