<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        /* =====================================================
         * 🔍 DEBUG 1: RAW REQUEST
         * ===================================================== */
        Log::info('PAYMENT RAW REQUEST', $request->all());

        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'nullable|string|max:50',
            'payment_date'   => 'nullable|date',
            'reference_no'   => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($request, $validated) {

            $customer = Customer::lockForUpdate()->findOrFail($validated['customer_id']);

            /* =====================================================
             * 🔍 DEBUG 2: CUSTOMER STATE
             * ===================================================== */
            Log::info('PAYMENT CUSTOMER STATE', [
                'customer_id' => $customer->id,
                'due_balance' => $customer->due_balance,
                'paying'      => $validated['amount'],
            ]);

            if ($validated['amount'] > $customer->due_balance) {
                abort(422, 'Payment exceeds due amount');
            }

            $remaining = (float) $validated['amount'];

            // ✅ FIX: use Payment model receipt generator
            $receiptNo = Payment::generateReceiptNo();

            $sales = Sale::where('customer_id', $customer->id)
                ->orderBy('sale_date')
                ->lockForUpdate()
                ->get();

            foreach ($sales as $sale) {
                if ($remaining <= 0) break;

                $due = $sale->due_amount;
                if ($due <= 0) continue;

                $applied = min($due, $remaining);

                Payment::create([
                    'receipt_no'     => $receiptNo,
                    'sale_id'        => $sale->id,
                    'customer_id'    => $customer->id,
                    'amount'         => $applied,
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'payment_date'   => $validated['payment_date'] ?? now(),
                    'reference_no'   => $validated['reference_no'] ?? null,
                ]);

                /* 🔍 DEBUG 3: PAYMENT APPLIED */
                Log::info('PAYMENT APPLIED TO SALE', [
                    'sale_id' => $sale->id,
                    'applied' => $applied,
                    'remaining_before' => $remaining,
                ]);

                $remaining -= $applied;
            }

            // Reduce customer due ONCE
            $customer->decrement('due_balance', $validated['amount']);

            /* 🔍 DEBUG 4: DUE UPDATED */
            Log::info('CUSTOMER DUE REDUCED', [
                'customer_id' => $customer->id,
                'reduced_by'  => $validated['amount'],
                'remaining_due' => $customer->due_balance - $validated['amount'],
            ]);

            return response()->json([
                'receipt_no' => $receiptNo,
                'amount'     => $validated['amount'],
            ], Response::HTTP_CREATED);
        });
    }
}
