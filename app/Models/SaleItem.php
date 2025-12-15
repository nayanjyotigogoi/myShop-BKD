<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',

        // NEW: snapshot of MRP / shop price at time of sale
        'mrp',

        'quantity',

        // unit_price = actual custom selling price per unit for this sale
        'unit_price',
        'line_total',

        // Optional: cost & profit snapshot
        'unit_cost',
        'line_profit',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

}
