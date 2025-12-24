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
        $date = optional($invoice->created_at)->format('Y-m-d')
            ?? Carbon::now()->format('Y-m-d');

        $shopName = 'MyShop';
        $invoiceNo = $invoice->invoice_number;

        $type = $invoice->saleReturn ? 'RETURN' : 'SALE';

        return "{$date}_{$shopName}_{$type}_{$invoiceNo}.pdf";
    }

    public function print(Invoice $invoice)
    {
        $invoice->load([
            'sale.items.product',
            'sale.payments',   // REQUIRED
            'saleReturn.items.product',
        ]);

        return Pdf::loadView('invoices.a4', compact('invoice'))
            ->setPaper('a4')
            ->stream($this->fileName($invoice));
    }

    public function download(Invoice $invoice)
    {
        $invoice->load([
            'sale.items.product',
            'sale.payments',
            'saleReturn.items.product',
        ]);

        return Pdf::loadView('invoices.a4', compact('invoice'))
            ->setPaper('a4')
            ->download($this->fileName($invoice));
    }
}
