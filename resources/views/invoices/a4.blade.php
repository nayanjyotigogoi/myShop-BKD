<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>

    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 10px;
        }

        .container {
            padding: 20px;
        }

        /* HEADER */
        table.header {
            width: 100%;
            margin-bottom: 20px;
        }

        .logo {
            width: 120px;
        }

        .shop-details {
            font-size: 11px;
            line-height: 1.5;
        }

        .invoice-details {
            text-align: right;
            font-size: 11px;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
        }

        /* ITEMS */
        table.items {
            width: 100%;
            border-collapse: collapse;
        }

        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 6px;
        }

        table.items th {
            background: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        /* TOTALS */
        table.totals {
            width: 100%;
            margin-top: 15px;
        }

        table.totals td {
            padding: 5px;
        }

        .label {
            text-align: right;
        }

        .value {
            width: 140px;
            text-align: right;
        }

        .grand-total {
            font-size: 14px;
            font-weight: bold;
        }

        /* NOTES */
        .note {
            margin-top: 15px;
            font-size: 10px;
        }
    </style>
</head>
<body>

<footer>
    Page <span class="pageNumber"></span> of <span class="totalPages"></span>
</footer>

<div class="container">

    {{-- ================= HEADER ================= --}}
    <table class="header">
        <tr>
            <td width="30%">
                <img src="{{ public_path('logo.png') }}" class="logo">
            </td>
            <td width="40%" class="shop-details">
                <strong>MyShop Pvt Ltd</strong><br>
                123, Main Market Road<br>
                Guwahati, Assam – 781001<br>
                GSTIN: 18ABCDE1234F1Z5<br>
                Phone: +91 98765 43210
            </td>
            <td width="30%" class="invoice-details">
                <div class="invoice-title">INVOICE</div>
                Invoice No: <strong>{{ $invoice->invoice_number }}</strong><br>
                Date: {{ optional($invoice->created_at)->format('d-m-Y') }}
            </td>
        </tr>
    </table>

    {{-- ================= ITEMS ================= --}}
    @php
        $subTotal = 0;
        $i = 1;
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>

            {{-- SALE ITEMS --}}
            @if($invoice->sale)
                @foreach($invoice->sale->items as $item)
                    @php
                        $price  = $item->unit_price;
                        $amount = $item->line_total;
                        $subTotal += $amount;
                    @endphp
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ $item->product->name ?? '-' }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">{{ number_format($price, 2) }}</td>
                        <td class="text-right">{{ number_format($amount, 2) }}</td>
                    </tr>
                @endforeach
            @endif

            {{-- SALE RETURN ITEMS --}}
            @if($invoice->saleReturn)
                @foreach($invoice->saleReturn->items as $item)
                    @php
                        $price  = $item->unit_price;
                        $amount = $item->line_total;
                        $subTotal -= $amount;
                    @endphp
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ $item->product->name ?? '-' }} (Return)</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">{{ number_format($price, 2) }}</td>
                        <td class="text-right">-{{ number_format($amount, 2) }}</td>
                    </tr>
                @endforeach
            @endif

        </tbody>
    </table>

    {{-- ================= GST & TOTALS ================= --}}
    @php
        $discount = $invoice->discount ?? 0;
        $taxableAmount = $subTotal - $discount;

        $gstRate  = 18;
        $cgstRate = $sgstRate = $gstRate / 2;

        $cgst = ($taxableAmount * $cgstRate) / 100;
        $sgst = ($taxableAmount * $sgstRate) / 100;

        $grandTotal = $taxableAmount + $cgst + $sgst;
    @endphp

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format($subTotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Discount</td>
            <td class="value">{{ number_format($discount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Taxable Amount</td>
            <td class="value">{{ number_format($taxableAmount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">CGST ({{ $cgstRate }}%)</td>
            <td class="value">{{ number_format($cgst, 2) }}</td>
        </tr>
        <tr>
            <td class="label">SGST ({{ $sgstRate }}%)</td>
            <td class="value">{{ number_format($sgst, 2) }}</td>
        </tr>
        <tr>
            <td class="label grand-total">Grand Total</td>
            <td class="value grand-total">{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </table>

    {{-- ================= AMOUNT IN WORDS ================= --}}
    @php
        function amountInWords($amount) {
            $fmt = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
            return ucfirst($fmt->format($amount)) . ' only';
        }
    @endphp

    <div class="note">
        <strong>Amount in Words:</strong> {{ amountInWords(round($grandTotal)) }}
    </div>

    <div class="note">
        This is a computer-generated invoice. No signature required.
    </div>

</div>
</body>
</html>
