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
        Log::info('PAYMENT RAW REQUEST', $request->all());

        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'nullable|string|max:50',
            'payment_date'   => 'nullable|date',
            'reference_no'   => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($validated) {

            $customer = Customer::lockForUpdate()->findOrFail($validated['customer_id']);

            if ($validated['amount'] > $customer->due_balance) {
                abort(422, 'Payment exceeds due amount');
            }

            $remaining = (float) $validated['amount'];
            $receiptNo = Payment::generateReceiptNo();

            $sales = Sale::where('customer_id', $customer->id)
                ->orderBy('sale_date')
                ->lockForUpdate()
                ->get();

            foreach ($sales as $sale) {
                if ($remaining <= 0) break;

                $paid = Payment::where('sale_id', $sale->id)->sum('amount');
                $due  = max(0, $sale->total - $paid);

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

                // ❌ COMMENTED (27.12.2025)
                // payment_status must NOT be updated here.
                // It is derived in Sale model.
                /*
                $sale->update([
                    'payment_status' => ($paid + $applied) >= $sale->total
                        ? 'paid'
                        : 'partial',
                ]);
                */

                $remaining -= $applied;
            }

            // ✅ VALID – KEEP
            $customer->decrement('due_balance', $validated['amount']);

            return response()->json([
                'receipt_no' => $receiptNo,
                'amount'     => $validated['amount'],
            ], Response::HTTP_CREATED);
        });
    }
}
