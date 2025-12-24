<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Payment;


class CustomerController extends Controller
{
    /**
     * List customers with due balance
     */
    public function index()
    {
        return response()->json(
            Customer::orderBy('name')->get()
        );
    }

    /**
     * Store new customer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        $customer = Customer::create([
            ...$validated,
            'due_balance' => 0,
        ]);

        return response()->json($customer, Response::HTTP_CREATED);
    }

    /**
     * Show single customer
     */
    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    /**
     * Update customer
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }

    /**
     * Delete customer (only if no due)
     */
    public function destroy(Customer $customer)
    {
        if ($customer->due_balance > 0) {
            return response()->json([
                'message' => 'Cannot delete customer with outstanding due'
            ], 422);
        }

        $customer->delete();

        return response()->noContent();
    }

public function payments(Customer $customer)
{
    return response()->json(
        \App\Models\Payment::where('customer_id', $customer->id)
            ->whereNotNull('receipt_no')
            ->select(
                'receipt_no',
                'payment_date',
                'payment_method'
            )
            ->selectRaw('SUM(amount) as amount')
            ->groupBy(
                'receipt_no',
                'payment_date',
                'payment_method'
            )
            ->orderBy('payment_date', 'desc')
            ->get()
    );
}




}
