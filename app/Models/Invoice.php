<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'invoice_type',       // sale | return
        'sale_id',
        'sale_return_id',
        'invoice_date',
        'gross_amount',
        'discount',
        'refund_amount',
        'net_amount',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }
}
