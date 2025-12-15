<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Generate yearly-reset invoice number
     * Example:
     *  Sale   → INV-2025-000001
     *  Return → CRN-2025-000001
     */
    public static function generate(string $type): string
    {
        $year = Carbon::now()->year;
        $prefix = $type === 'sale' ? 'INV' : 'CRN';

        $lastInvoice = Invoice::where('invoice_type', $type)
            ->whereYear('invoice_date', $year)
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastInvoice) {
            $lastSeq = (int) substr($lastInvoice->invoice_number, -6);
            $nextNumber = $lastSeq + 1;
        }

        return sprintf('%s-%d-%06d', $prefix, $year, $nextNumber);
    }
}
