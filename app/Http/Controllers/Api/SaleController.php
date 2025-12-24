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
        /* =====================================================
         * 🔍 DEBUG 1: RAW REQUEST (CRITICAL)
         * ===================================================== */
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

            /* =====================================================
             * NORMALIZE INPUT
             * ===================================================== */
            $customerId = !empty($validated['customer_id'])
                ? (int) $validated['customer_id']
                : null;

            $discount = (float) ($validated['discount'] ?? 0);

            // ✅ IMPORTANT FIX: read paid_now directly from request
            $paidNow = (float) $request->input('paid_now', 0);

            /* =====================================================
             * 🔍 DEBUG 2: NORMALIZED VALUES
             * ===================================================== */
            Log::info('SALE NORMALIZED VALUES', [
                'customer_id' => $customerId,
                'discount'    => $discount,
                'paid_now'    => $paidNow,
            ]);

            /* =====================================================
             * CREATE SALE
             * ===================================================== */
            $sale = Sale::create([
                'sale_date'   => $validated['sale_date'],
                'subtotal'    => 0,
                'discount'    => $discount,
                'total'       => 0,
                'customer_id' => $customerId,
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

                $lineTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $lineTotal;

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'mrp'        => $product->sell_price,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);

                $product->decrement('current_stock', $item['quantity']);
            }

            /* =====================================================
             * TOTALS
             * ===================================================== */
            $total = max(0, $subtotal - $discount);

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

    Log::info('SALE PAYMENT CREATED', [
        'sale_id' => $sale->id,
        'amount'  => $paidNow,
    ]);
}

            /* =====================================================
             * CUSTOMER DUE
             * ===================================================== */
            if ($customerId) {

                $due = max(0, $total - $paidNow);

                if ($due > 0) {
                    Customer::where('id', $customerId)
                        ->increment('due_balance', $due);

                    /* 🔍 DEBUG 4: DUE UPDATED */
                    Log::info('CUSTOMER DUE UPDATED', [
                        'customer_id' => $customerId,
                        'due_added'   => $due,
                    ]);
                }
            }

            /* =====================================================
             * RESPONSE
             * ===================================================== */
            return response()->json(
                $sale->load([
                    'items.product',
                    'payments',
                    'customer',
                    'invoices',
                ]),
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
