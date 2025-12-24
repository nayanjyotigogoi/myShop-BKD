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
        'customer_id',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'subtotal'  => 'decimal:2',
        'discount'  => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    /**
     * These values are derived and returned in API responses
     */
    protected $appends = [
        'refund_total',
        'net_total',
        'paid_amount',
        'due_amount',
        'payment_status',
    ];

    /* ================= RELATIONS ================= */

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function invoices()
{
    return $this->hasMany(Invoice::class);
}


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /* ================= COMPUTED ATTRIBUTES ================= */

    /**
     * Total refunded amount (from sale returns)
     */
    public function getRefundTotalAttribute(): float
    {
        return (float) $this->returns()->sum('refund_amount');
    }

    /**
     * Net total after returns
     */
    public function getNetTotalAttribute(): float
    {
        $total = (float) ($this->total ?? 0);
        $refund = (float) $this->refund_total;

        return max(0, $total - $refund);
    }

    /**
     * Total amount paid (all payments)
     */
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Due amount after payments & returns
     */
    public function getDueAmountAttribute(): float
    {
        return max(0, $this->net_total - $this->paid_amount);
    }

    /**
     * Payment status derived from net total
     */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_amount <= 0) {
            return 'unpaid';
        }

        if ($this->paid_amount < $this->net_total) {
            return 'partial';
        }

        return 'paid';
    }
}
