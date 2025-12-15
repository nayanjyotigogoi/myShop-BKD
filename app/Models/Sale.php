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
        // 'user_id', // later if needed
    ];

    protected $casts = [
        'sale_date' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    // App\Models\Sale.php

    protected $appends = ['refund_total', 'net_total'];

    public function getRefundTotalAttribute()
    {
        return $this->returns()->sum('refund_amount');
    }

    public function getNetTotalAttribute()
    {
        return max(0, $this->total - $this->refund_total);
    }


}
