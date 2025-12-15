<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = [
        'sale_id',
        'return_date',
        'refund_amount',
        'refund_method',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    /* ================= RELATIONS ================= */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    //Invoice
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

}
