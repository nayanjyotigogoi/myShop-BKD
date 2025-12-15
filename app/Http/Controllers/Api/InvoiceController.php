<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    private function fileName(Invoice $invoice): string
    {
        // Invoice date (fallback safe)
        $date = optional($invoice->created_at)->format('Y-m-d')
            ?? Carbon::now()->format('Y-m-d');

        $shopName = 'MyShop'; // filesystem safe
        $invoiceNo = $invoice->invoice_number;

        // Detect invoice type
        $type = 'SALE';
        if ($invoice->saleReturn) {
            $type = 'RETURN';
        }

        return "{$date}_{$shopName}_{$type}_{$invoiceNo}.pdf";
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['sale.items.product', 'saleReturn.items.product']);

        $pdf = Pdf::loadView('invoices.a4', [
            'invoice' => $invoice,
        ])->setPaper('a4');

        return $pdf->stream(
            $this->fileName($invoice)
        );
    }

    public function download(Invoice $invoice)
    {
        $invoice->load(['sale.items.product', 'saleReturn.items.product']);

        $pdf = Pdf::loadView('invoices.a4', [
            'invoice' => $invoice,
        ])->setPaper('a4');

        return $pdf->download(
            $this->fileName($invoice)
        );
    }
}
