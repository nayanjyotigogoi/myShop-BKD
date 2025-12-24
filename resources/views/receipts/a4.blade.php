<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receiptNo }}</title>

    <style>
        @page { margin: 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px; }
        .title { font-size: 20px; font-weight: bold; text-align: right; }
        .box { border: 1px solid #000; padding: 10px; margin-top: 15px; }
        .right { text-align: right; }
        .note { font-size: 10px; margin-top: 15px; }
    </style>
</head>
<body>

@php
    $payment = $payments->first();
    $customer = $payment->customer;

    // ✅ ADDED on 18.12.2025
    // Net receipt total (refunds are negative)
    $total = $payments->sum('amount');
    $isRefund = $total < 0;
@endphp

<table>
    <tr>
        <td>
            <strong>MyShop Pvt Ltd</strong><br>
            Guwahati, Assam
        </td>
        <td class="title">
            {{ $isRefund ? 'REFUND RECEIPT' : 'PAYMENT RECEIPT' }}
        </td>
    </tr>
</table>

<hr>

<table>
    <tr>
        <td><strong>Receipt No:</strong> {{ $receiptNo }}</td>
        <td class="right">
            <strong>Date:</strong>
            {{ optional($payment->payment_date)->format('d-m-Y') }}
        </td>
    </tr>
</table>

<br>

<table>
    <tr>
        <td>{{ $isRefund ? 'Refunded To:' : 'Received From:' }}</td>
        <td class="right">{{ $customer->name ?? 'Walk-in Customer' }}</td>
    </tr>
</table>

<div class="box">
    <table>
        <tr>
            <td>{{ $isRefund ? 'Refund Amount' : 'Amount Received' }}</td>
            <td class="right">₹{{ number_format(abs($total), 2) }}</td>
        </tr>
        <tr>
            <td>Payment Mode</td>
            <td class="right">{{ strtoupper($payment->payment_method ?? 'CASH') }}</td>
        </tr>
    </table>
</div>

@if($payments->count() > 0)
<div class="box">
    <strong>Invoice Adjustment</strong>
    <table border="1" style="margin-top:8px;">
        <tr>
            <th align="left">Invoice No</th>
            <th align="right">Amount</th>
        </tr>
        @foreach($payments as $p)
            @if($p->sale)
            <tr>
                <td>{{ optional($p->sale->invoice)->invoice_number }}</td>
                <td align="right">₹{{ number_format(abs($p->amount), 2) }}</td>
            </tr>
            @endif
        @endforeach
    </table>
</div>
@endif

<div class="box">
    <table>
        <tr>
            <td>Customer Due After This Receipt</td>
            <td class="right">
                ₹{{ number_format($customer->due_balance ?? 0, 2) }}
            </td>
        </tr>
    </table>
</div>

<div class="note">
    This is a computer-generated receipt. No signature required.
</div>

</body>
</html>
