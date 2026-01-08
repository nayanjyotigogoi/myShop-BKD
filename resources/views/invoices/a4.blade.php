<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>

    <style>
        @page { margin: 24px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        footer {
            position: fixed;
            bottom: -12px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }

        .container {
            width: 100%;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            box-sizing: border-box;
        }

        /* ================= WATERMARK ================= */

        .watermark {
            position: fixed;
            top: 45%;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 64px;
            font-weight: 800;
            transform: rotate(-30deg);
            z-index: -1;
            letter-spacing: 4px;
        }

        .watermark.paid    { color: rgba(16,185,129,0.18); }
        .watermark.partial { color: rgba(245,158,11,0.22); }
        .watermark.due     { color: rgba(239,68,68,0.20); }
        .watermark.return  { color: rgba(220,38,38,0.22); }

        /* ================= TABLE BASE ================= */

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        /* ================= HEADER ================= */

        .header-table td {
            padding: 6px;
            vertical-align: top;
        }

        .brand {
            font-size: 16px;
            font-weight: 700;
        }

        .company-info {
            font-size: 10px;
            line-height: 1.4;
            color: #374151;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: 800;
        }

        .invoice-meta {
            font-size: 10px;
            color: #374151;
        }

        /* ================= ITEMS ================= */

        .items th,
        .items td {
            padding: 6px;
            font-size: 10.5px;
            border-bottom: 1px solid #e5e7eb;
            overflow: hidden;
            word-wrap: break-word;
        }

        .items th {
            background: #f9fafb;
            font-weight: 700;
            color: #374151;
        }

        .text-right {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .discount {
            color: #b91c1c;
        }

        /* ================= TOTALS ================= */

        .totals-table td {
            padding: 5px;
            font-size: 11px;
        }

        .label {
            text-align: right;
            color: #374151;
        }

        .value {
            text-align: right;
            width: 120px;
            font-variant-numeric: tabular-nums;
        }

        .grand-total {
            font-weight: bold;
            font-size: 13px;
            /* font-weight: 1200; */
            border-top: 1px solid #e5e7eb;
        }

        /* ================= NOTES ================= */

        .note {
            margin-top: 14px;
            font-size: 9.5px;
            color: #374151;
        }

        .muted {
            color: #6b7280;
        }
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

        $subtotal = $items->sum(fn($i) => $i->line_total);
        $mrpValue = $items->sum(fn($i) => ($i->mrp ?? $i->unit_price) * $i->quantity);
        $youSaved = max(0, $mrpValue - $subtotal);

        $overallDiscount = (float) ($sale->discount ?? 0);
        $invoiceTotal = (float) ($sale->total ?? 0);

        $initialPayment = $sale
            ? (float) $sale->payments
                ->where('amount', '>', 0)
                ->where('created_at', '<=', $invoice->created_at)
                ->sum('amount')
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

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td width="33%">
                @if(file_exists(public_path('logo.png')))
                    <img src="{{ public_path('logo.png') }}" width="120">
                @else
                    <div class="brand">MyShop</div>
                @endif
            </td>

            <td width="34%" class="company-info">
                <strong>MyShop Pvt Ltd</strong><br>
                Guwahati, Assam<br>
                GSTIN: 18ABCDE1234F1Z5
            </td>

            <td width="33%" class="text-right">
                <div class="invoice-title">
                    {{ $isReturn ? 'CREDIT NOTE' : 'INVOICE' }}
                </div>
                <div class="invoice-meta">
                    No: <strong>{{ $invoice->invoice_number }}</strong><br>
                    Date: {{ optional($invoice->invoice_date)->format('d-m-Y') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- ITEMS -->
    <table class="items" style="margin-top:14px;">
        <thead>
            <tr>
                <th width="5%">Sl</th>
                <th width="35%">Item</th>
                <th width="8%" class="text-right">Qty</th>
                <th width="12%" class="text-right">MRP</th>
                <th width="12%" class="text-right">Selling</th>
                <th width="13%" class="text-right">Discount</th>
                <th width="15%" class="text-right">Amount</th>
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

    <!-- TOTALS -->
    <table class="totals-table" style="margin-top:20px;">
        <tr>
            <td width="60%"></td>
            <td width="40%">
                <table width="100%" class="totals-table">
                    @if(!$isReturn)
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="value">{{ number_format($subtotal, 2) }}</td>
                        </tr>

                        @if($youSaved > 0)
                        <tr>
                            <td class="label discount">You Saved</td>
                            <td class="value discount">-{{ number_format($youSaved, 2) }}</td>
                        </tr>
                        @endif

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
                        <td class="value grand-total">
                            {{ number_format($invoiceTotal, 2) }}
                        </td>
                    </tr>

                    @unless($isReturn)
                        <tr>
                            <td class="label">Paid</td>
                            <td class="value">{{ number_format($initialPayment, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label grand-total">Balance Due</td>
                            <td class="value grand-total">
                                {{ number_format($balanceDue, 2) }}
                            </td>
                        </tr>
                    @endunless
                </table>
            </td>
        </tr>
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

    <div class="note muted">
        This is a computer-generated document. No signature required.
    </div>

</div>

</body>
</html>
