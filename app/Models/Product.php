<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',   // product type (T-Shirt, Jeans, etc.)
        'gender',     // target group: male, female, boys, girls, unisex
        'size',
        'color',
        'buy_price',
        'sell_price',
        'current_stock',
    ];

    // A product appears in many purchase line items
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // A product appears in many sale line items
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Optional: convenience accessor for stock value
    public function getStockValueAttribute()
    {
        return $this->current_stock * $this->buy_price;
    }

    //return
    public function saleReturnItems()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    

}
