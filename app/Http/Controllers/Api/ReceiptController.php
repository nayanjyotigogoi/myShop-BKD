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
        $payment = Payment::where('receipt_no', $receiptNo)
            ->with([
                'customer',
                'sale.invoice',
                'saleReturn.invoice',
            ])
            ->firstOrFail(); // ✅ ONE payment only

        return Pdf::loadView('receipts.a4', [
                'payment'  => $payment,
                'receiptNo'=> $receiptNo,
            ])
            ->setPaper('a4')
            ->stream($this->fileName($receiptNo));
    }

    public function download($receiptNo)
    {
        $payment = Payment::where('receipt_no', $receiptNo)
            ->with([
                'customer',
                'sale.invoice',
                'saleReturn.invoice',
            ])
            ->firstOrFail();

        return Pdf::loadView('receipts.a4', [
                'payment'  => $payment,
                'receiptNo'=> $receiptNo,
            ])
            ->setPaper('a4')
            ->download($this->fileName($receiptNo));
    }
}
