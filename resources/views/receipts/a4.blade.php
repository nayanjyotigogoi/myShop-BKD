<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receiptNo }}</title>

    <style>
        @page { margin: 22px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td, th {
            padding: 6px;
            vertical-align: top;
        }

        /* ================= WATERMARK ================= */

        .watermark {
            position: fixed;
            top: 45%;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 58px;
            font-weight: 800;
            transform: rotate(-30deg);
            z-index: -1;
            letter-spacing: 3px;
            pointer-events: none;
        }

        .wm-paid    { color: rgba(16,185,129,0.18); }
        .wm-partial { color: rgba(245,158,11,0.25); }
        .wm-refund  { color: rgba(220,38,38,0.22); }

        /* ================= HEADER ================= */

        .header td {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .company {
            font-size: 13px;
            font-weight: 700;
        }

        .company small {
            font-size: 10px;
            color: #6b7280;
            display: block;
            margin-top: 2px;
        }

        .title {
            font-size: 18px;
            font-weight: 800;
            text-align: right;
        }

        /* ================= META ================= */

        .meta td {
            font-size: 10.5px;
            color: #374151;
            padding-top: 10px;
        }

        .right {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* ================= CARD ================= */

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            margin-top: 14px;
        }

        .card-title {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #374151;
        }

        .amount {
            font-size: 16px;
            font-weight: 800;
        }

        .currency {
            font-size: 12px;
            vertical-align: top;
        }

        /* ================= BARCODE ================= */

        .barcode-wrap {
            text-align: center;
            margin-top: 16px;
        }

        .barcode-wrap img {
            max-width: 260px;
        }

        .barcode-label {
            font-size: 9px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ================= NOTE ================= */

        .note {
            margin-top: 16px;
            font-size: 9.5px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>

@php
    $customer = $payment->customer;
    $amount   = (float) $payment->amount;
    $isRefund = $amount < 0;

    $invoiceTotal = $payment->sale?->invoice?->total ?? null;

    if ($isRefund) {
        $wmText  = 'REFUNDED';
        $wmClass = 'wm-refund';
    } elseif ($invoiceTotal && abs($amount) < $invoiceTotal) {
        $wmText  = 'PARTIAL PAYMENT';
        $wmClass = 'wm-partial';
    } else {
        $wmText  = 'PAID';
        $wmClass = 'wm-paid';
    }

    /**
     * -------------------------------------------------
     * Generate REAL Code-39 barcode as base64 PNG
     * -------------------------------------------------
     */
    function barcodeBase64($text)
    {
        $text = strtoupper("*{$text}*"); // Code-39 requires *
        $narrow = 2;
        $wide = 6;
        $height = 50;

        $codes = [
            '0'=>'nnnwwnwnn','1'=>'wnnwnnnnw','2'=>'nnwwnnnnw','3'=>'wnwwnnnnn',
            '4'=>'nnnwwnnnw','5'=>'wnnwwnnnn','6'=>'nnwwwnnnn','7'=>'nnnwnnwnw',
            '8'=>'wnnwnnwnn','9'=>'nnwwnnwnn','A'=>'wnnnnwnnw','B'=>'nnwnnwnnw',
            'C'=>'wnwnnwnnn','D'=>'nnnnwwnnw','E'=>'wnnnwwnnn','F'=>'nnwnwwnnn',
            'G'=>'nnnnnwwnw','H'=>'wnnnnwwnn','I'=>'nnwnnwwnn','J'=>'nnnnwwwnn',
            'K'=>'wnnnnnnww','L'=>'nnwnnnnww','M'=>'wnwnnnnwn','N'=>'nnnnwnnww',
            'O'=>'wnnnwnnwn','P'=>'nnwnwnnwn','Q'=>'nnnnnnwww','R'=>'wnnnnnwwn',
            'S'=>'nnwnnnwwn','T'=>'nnnnwnwwn','U'=>'wwnnnnnnw','V'=>'nwwnnnnnw',
            'W'=>'wwwnnnnnn','X'=>'nwnnwnnnw','Y'=>'wwnnwnnnn','Z'=>'nwwnwnnnn',
            '-'=>'nwnnnnwnw','.'=>'wwnnnnwnn',' '=>'nwwnnnwnn','$'=>'nwnwnwnnn',
            '/'=>'nwnwnnnwn','+'=>'nwnnnwnwn','%'=>'nnnwnwnwn','*'=>'nwnnwnwnn'
        ];

        $width = 0;
        foreach (str_split($text) as $char) {
            foreach (str_split($codes[$char]) as $bar) {
                $width += ($bar === 'n') ? $narrow : $wide;
            }
            $width += $narrow;
        }

        $img = imagecreate($width, $height);
        $white = imagecolorallocate($img, 255,255,255);
        $black = imagecolorallocate($img, 0,0,0);

        $x = 0;
        foreach (str_split($text) as $char) {
            foreach (str_split($codes[$char]) as $i => $bar) {
                $w = ($bar === 'n') ? $narrow : $wide;
                if ($i % 2 === 0) {
                    imagefilledrectangle($img, $x, 0, $x+$w, $height, $black);
                }
                $x += $w;
            }
            $x += $narrow;
        }

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    $barcodeImage = barcodeBase64($receiptNo);
@endphp

<!-- ================= WATERMARK ================= -->

<div class="watermark {{ $wmClass }}">
    {{ $wmText }}
</div>

<!-- ================= HEADER ================= -->

<table class="header">
    <tr>
        <td width="60%">
            <div class="company">
                MyShop Pvt Ltd
                <small>Guwahati, Assam</small>
            </div>
        </td>
        <td width="40%" class="title">
            {{ $isRefund ? 'REFUND RECEIPT' : 'PAYMENT RECEIPT' }}
        </td>
    </tr>
</table>

<!-- ================= META ================= -->

<table class="meta">
    <tr>
        <td><strong>Receipt No:</strong> {{ $receiptNo }}</td>
        <td class="right">
            <strong>Date:</strong>
            {{ optional($payment->payment_date)->format('d-m-Y') }}
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td>{{ $isRefund ? 'Refunded To:' : 'Received From:' }}</td>
        <td class="right">{{ $customer->name ?? 'Walk-in Customer' }}</td>
    </tr>
</table>

<!-- ================= AMOUNT ================= -->

<div class="card">
    <table>
        <tr>
            <td class="card-title">
                {{ $isRefund ? 'Refund Amount' : 'Amount Received' }}
            </td>
            <td class="right amount">
                <span class="currency"></span>{{ number_format(abs($amount), 2) }}
            </td>
        </tr>
        <tr>
            <td class="card-title">Payment Mode</td>
            <td class="right">
                {{ strtoupper($payment->payment_method ?? 'CASH') }}
            </td>
        </tr>
    </table>
</div>

<!-- ================= ADJUSTED INVOICE ================= -->

@if($payment->sale && $payment->sale->invoice)
<div class="card">
    <div class="card-title">Adjusted Against Invoice</div>
    <table>
        <tr>
            <td width="60%">{{ $payment->sale->invoice->invoice_number }}</td>
            <td width="40%" class="right">
                ₹{{ number_format(abs($amount), 2) }}
            </td>
        </tr>
    </table>
</div>
@endif

<!-- ================= BARCODE ================= -->

<div class="barcode-wrap">
    <img src="{{ $barcodeImage }}" alt="Receipt Barcode">
    <div class="barcode-label">{{ $receiptNo }}</div>
</div>

<!-- ================= NOTE ================= -->

<div class="note">
    This is a computer-generated receipt. No signature required.
</div>

</body>
</html>
