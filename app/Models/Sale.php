<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_date',
        'subtotal',
        'discount',
        'total',
        'bill_number',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
    ];

    protected $appends = ['refund_total', 'net_total'];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /* ================= COMPUTED ================= */

    public function getRefundTotalAttribute()
    {
        return $this->returns()->sum('refund_amount');
    }

    public function getNetTotalAttribute()
    {
        return max(0, $this->total - $this->refund_total);
    }

    //customer
    public function customer()
{
    return $this->belongsTo(Customer::class);
}

public function payments()
{
    return $this->hasMany(Payment::class);
}

/* ===== COMPUTED ===== */

public function getPaidAmountAttribute()
{
    return $this->payments()->sum('amount');
}

public function getDueAmountAttribute()
{
    return max(0, $this->total - $this->paid_amount);
}

public function getPaymentStatusAttribute()
{
    if ($this->paid_amount <= 0) {
        return 'unpaid';
    }

    if ($this->paid_amount < $this->total) {
        return 'partial';
    }

    return 'paid';
}

}
