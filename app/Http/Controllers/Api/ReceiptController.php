<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReceiptController extends Controller
{
    private function fileName(string $receiptNo): string
    {
        $date = Carbon::now()->format('Y-m-d');
        return "{$date}_RECEIPT_{$receiptNo}.pdf";
    }

    public function print($receiptNo)
    {
        $payments = Payment::where('receipt_no', $receiptNo)
            ->with([
                'customer',
                'sale.invoice',
                'saleReturn.invoice', // ✅ added
            ])
            ->get();

        abort_if($payments->isEmpty(), 404);

        return Pdf::loadView('receipts.a4', [
                'payments'  => $payments,
                'receiptNo' => $receiptNo,
            ])
            ->setPaper('a4')
            ->stream($this->fileName($receiptNo));
    }

    public function download($receiptNo)
    {
        $payments = Payment::where('receipt_no', $receiptNo)
            ->with([
                'customer',
                'sale.invoice',
                'saleReturn.invoice', // ✅ added
            ])
            ->get();

        abort_if($payments->isEmpty(), 404);

        return Pdf::loadView('receipts.a4', [
                'payments'  => $payments,
                'receiptNo' => $receiptNo,
            ])
            ->setPaper('a4')
            ->download($this->fileName($receiptNo));
    }
}
