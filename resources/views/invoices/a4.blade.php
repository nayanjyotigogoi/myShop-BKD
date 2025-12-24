<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>

    <style>
        @page { margin: 20px; }
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
        .container { padding: 20px; position: relative; }

        .watermark {
            position: fixed;
            top: 45%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 70px;
            font-weight: bold;
            transform: rotate(-30deg);
            z-index: -1;
            pointer-events: none;
            color: rgba(200, 0, 0, 0.15);
        }
        .watermark.paid { color: rgba(0,150,0,0.18); }
        .watermark.partial { color: rgba(255,165,0,0.22); }
        .watermark.return { color: rgba(200,0,0,0.18); }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background: #f2f2f2; }
        .text-right { text-align: right; }

        table.totals td { border: none; padding: 5px; }
        .label { text-align: right; }
        .value { width: 140px; text-align: right; }
        .grand-total { font-size: 14px; font-weight: bold; }

        .note { margin-top: 15px; font-size: 10px; }
        .discount { color: #b00020; }
    </style>
</head>
<body>

<footer>
    Page <span class="page"></span> of <span class="pages"></span>
</footer>

<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_text(520, 820, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 9);
    }
</script>

@php
    $isReturn = $invoice->invoice_type === 'return';

    if ($isReturn) {
        // ✅ ADDED on 18.12.2025
        // Credit note must ONLY show returned items (no discount recalculation)
        $items = $invoice->saleReturn->items ?? collect();
        $invoiceTotal = (float) $invoice->refund_amount;

        $initialPayment = 0;
        $balanceDue = 0;
        $overallDiscount = 0;

        $statusText = 'RETURN';
        $statusClass = 'return';

    } else {
        $sale = $invoice->sale;
        $items = $sale?->items ?? collect();

        // ✅ ADDED on 18.12.2025
        // Subtotal BEFORE discount
        $grossSubtotal = $items->sum(fn($i) => $i->mrp * $i->quantity);

        $overallDiscount = (float) ($sale->discount ?? 0);

        $invoiceTotal = (float) ($sale->total ?? 0);

        // ✅ ADDED on 18.12.2025
        // Net paid = payments minus refunds
        
         // ✅ ADDED on 19.12.2025
$initialPayment = $sale
    ? (float) $sale->payments->where('amount', '>', 0)->sum('amount')
    : 0;


        $balanceDue = max(0, $invoiceTotal - $initialPayment);

        if ($initialPayment >= $invoiceTotal && $invoiceTotal > 0) {
            $statusText = 'PAID';
            $statusClass = 'paid';
        } elseif ($initialPayment > 0) {
            $statusText = 'PARTIALLY PAID';
            $statusClass = 'partial';
        } else {
            $statusText = 'DUE';
            $statusClass = 'due';
        }
    }
@endphp

<div class="watermark {{ $statusClass }}">{{ $statusText }}</div>

<div class="container">

<table>
    <tr>
        <td width="30%">
            @if(file_exists(public_path('logo.png')))
                <img src="{{ public_path('logo.png') }}" width="120">
            @else
                <strong>MyShop</strong>
            @endif
        </td>
        <td width="40%">
            <strong>MyShop Pvt Ltd</strong><br>
            Guwahati, Assam<br>
            GSTIN: 18ABCDE1234F1Z5
        </td>
        <td width="30%" class="text-right">
            <h2>{{ $isReturn ? 'CREDIT NOTE' : 'INVOICE' }}</h2>
            No: <strong>{{ $invoice->invoice_number }}</strong><br>
            Date: {{ optional($invoice->invoice_date)->format('d-m-Y') }}
        </td>
    </tr>
</table>

<br>

<table>
    <thead>
        <tr>
            <th>Sl</th>
            <th>Item</th>
            <th class="text-right">Qty</th>
            <th class="text-right">MRP</th>
            <th class="text-right">Selling</th>
            <th class="text-right">Discount</th>
            <th class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
            @php
                $mrp = $item->mrp ?? $item->unit_price;
                $itemDiscount = ($mrp - $item->unit_price) * $item->quantity;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($mrp, 2) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right discount">
                    {{ $itemDiscount > 0 ? '-' . number_format($itemDiscount, 2) : '0.00' }}
                </td>
                <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    @if(!$isReturn)
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format($grossSubtotal, 2) }}</td>
        </tr>

        @if($overallDiscount > 0)
        <tr>
            <td class="label discount">Overall Discount</td>
            <td class="value discount">-{{ number_format($overallDiscount, 2) }}</td>
        </tr>
        @endif
    @endif

    <tr>
        <td class="label grand-total">
            {{ $isReturn ? 'Refund Total' : 'Invoice Total' }}
        </td>
        <td class="value grand-total">{{ number_format($invoiceTotal, 2) }}</td>
    </tr>

    @unless($isReturn)
        <tr>
            <td class="label">Paid</td>
            <td class="value">{{ number_format($initialPayment, 2) }}</td>
        </tr>
        <tr>
            <td class="label grand-total">Balance Due</td>
            <td class="value grand-total">{{ number_format($balanceDue, 2) }}</td>
        </tr>
    @endunless
</table>

@php
    function amountInWordsSafe($amount) {
        if (!class_exists(\NumberFormatter::class)) {
            return $amount . ' only';
        }
        $fmt = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
        return ucfirst($fmt->format($amount)) . ' only';
    }
@endphp

<div class="note">
    <strong>{{ $isReturn ? 'Refund Amount' : 'Amount Paid' }} (in Words):</strong>
    {{ amountInWordsSafe(round($isReturn ? $invoiceTotal : $initialPayment)) }}
</div>

<div class="note">
    This is a computer-generated document. No signature required.
</div>

</div>
</body>
</html>
