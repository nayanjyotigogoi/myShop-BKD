<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    /**
     * Store a sale return (partial / full)
     *
     * FINAL LOGIC – LOCKED ON 19.12.2025
     */
    public function store(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'refund_method'        => 'nullable|string|in:cash,upi,card,bank',
            'reason'               => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($sale, $validated, $request) {

            /* =====================================================
             * LOCK SALE + CUSTOMER
             * ===================================================== */
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            $customer = $sale->customer
                ? Customer::lockForUpdate()->find($sale->customer_id)
                : null;

            $refundTotal = 0;
            $returnLines = [];

            /* =====================================================
             * VALIDATE + CALCULATE RETURN AMOUNT
             * Selling price ONLY (discount already consumed)
             * ===================================================== */
            foreach ($validated['items'] as $item) {

                $saleItem = SaleItem::where('id', $item['sale_item_id'])
                    ->where('sale_id', $sale->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $alreadyReturned = SaleReturnItem::where('sale_item_id', $saleItem->id)
                    ->sum('quantity');

                $availableQty = $saleItem->quantity - $alreadyReturned;

                if ($item['quantity'] > $availableQty) {
                    abort(422, 'Return quantity exceeds available quantity');
                }

                $lineTotal = $saleItem->unit_price * $item['quantity'];

                $refundTotal += $lineTotal;

                $returnLines[] = [
                    'saleItem' => $saleItem,
                    'qty'      => $item['quantity'],
                    'total'    => $lineTotal,
                ];
            }

            /* =====================================================
             * ORIGINAL SALE STATE (IMMUTABLE)
             * ===================================================== */
            $saleTotal        = (float) $sale->total;
            $paidInSale       = (float) $sale->payments()->sum('amount');
            $saleDueBefore    = max(0, $saleTotal - $paidInSale);
            $customerDueBefore = $customer ? (float) $customer->due_balance : 0;

            /* =====================================================
             * REFUND STRATEGY DECISION
             * ===================================================== */

            $adjustSaleDue   = 0;
            $adjustCustDue   = 0;
            $cashRefund      = 0;

            /**
             * =====================================================
             * CASE A: refund_method NOT provided → SYSTEM DECIDES
             * Priority:
             * 1️⃣ Sale due
             * 2️⃣ Customer previous due
             * 3️⃣ Cash refund
             * =====================================================
             */
            if (empty($validated['refund_method'])) {

                $adjustSaleDue = min($refundTotal, $saleDueBefore);
                $remaining = $refundTotal - $adjustSaleDue;

                if ($remaining > 0 && $customer) {
                    $adjustCustDue = min($remaining, $customerDueBefore);
                    $remaining -= $adjustCustDue;
                }

                if ($remaining > 0) {
                    if ($remaining > $paidInSale) {
                        abort(422, 'Refund exceeds amount paid');
                    }
                    $cashRefund = $remaining;
                }
            }

            /**
             * =====================================================
             * CASE B: refund_method PROVIDED (cash / upi / card / bank)
             *
             * RULE:
             * - Ignore PREVIOUS customer due
             * - Respect THIS sale only
             * =====================================================
             */
            else {

                // 1️⃣ Reduce CURRENT sale due first
                $adjustSaleDue = min($refundTotal, $saleDueBefore);
                $remaining = $refundTotal - $adjustSaleDue;

                // 2️⃣ Refund ONLY from paid amount in THIS sale
                if ($remaining > 0) {

                    if ($paidInSale <= 0) {
                        abort(422, 'No payment made for this sale to refund');
                    }

                    if ($remaining > $paidInSale) {
                        abort(422, 'Refund exceeds amount paid for this sale');
                    }

                    $cashRefund = $remaining;
                }
            }

            /* =====================================================
             * WALK-IN CUSTOMER PROTECTION
             * ===================================================== */
            if (($adjustCustDue > 0 || $cashRefund > 0) && !$sale->customer_id) {
                abort(422, 'Refund or adjustment not allowed for walk-in customer');
            }

            /* =====================================================
             * CREATE SALE RETURN MASTER
             * ===================================================== */
            $saleReturn = SaleReturn::create([
                'sale_id'       => $sale->id,
                'return_date'   => now(),
                'refund_amount' => $refundTotal,
                'refund_method' => $validated['refund_method'] ?? null,
                'reason'        => $validated['reason'] ?? null,
                'created_by'    => $request->user()->id ?? null,
            ]);

            /* =====================================================
             * CREATE RETURN ITEMS + RESTORE STOCK
             * ===================================================== */
            foreach ($returnLines as $line) {

                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id'   => $line['saleItem']->id,
                    'product_id'     => $line['saleItem']->product_id,
                    'quantity'       => $line['qty'],
                    'unit_price'     => $line['saleItem']->unit_price,
                    'line_total'     => $line['total'],
                ]);

                Product::where('id', $line['saleItem']->product_id)
                    ->increment('current_stock', $line['qty']);
            }

            /* =====================================================
             * CREDIT NOTE (NEW INVOICE – ORIGINAL UNCHANGED)
             * ===================================================== */
            $invoice = Invoice::create([
                'invoice_number' => InvoiceService::generate('return'),
                'invoice_type'   => 'return',
                'sale_id'        => $sale->id,
                'sale_return_id' => $saleReturn->id,
                'invoice_date'   => now(),
                'gross_amount'   => $refundTotal,
                'discount'       => 0,
                'refund_amount'  => $refundTotal,
                'net_amount'     => 0,
            ]);

            /* =====================================================
             * CASH / UPI / CARD REFUND (NEGATIVE PAYMENT)
             * ===================================================== */
            if ($cashRefund > 0) {
                Payment::create([
                    'receipt_no'     => Payment::generateReceiptNo(),
                    'sale_id'        => $sale->id,
                    'sale_return_id' => $saleReturn->id,
                    'customer_id'    => $sale->customer_id,
                    'amount'         => -$cashRefund,
                    'payment_method' => $validated['refund_method'],
                    'payment_date'   => now(),
                ]);
            }

            /* =====================================================
             * CUSTOMER DUE FINAL UPDATE
             * ===================================================== */
            if ($customer) {

                $newCustomerDue = max(
                    0,
                    $customerDueBefore
                    - $adjustCustDue
                    - $adjustSaleDue
                );

                $customer->update([
                    'due_balance' => $newCustomerDue,
                ]);
            }

            return response()->json([
                'success' => true,
                'return'  => $saleReturn->load(['items.product', 'invoice']),
            ], 201);
        });
    }
}
