<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'sale_id',
        'customer_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference_no',
        'receipt_no',   // ✅ REQUIRED
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /* ================= RELATIONS ================= */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public static function generateReceiptNo(): string
    {
        return 'RCPT-' . now()->format('YmdHis');
    }

}
