<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_id', 'product_id', 'product_variant_id', 'product_name_snapshot',
    'variant_name_snapshot', 'sku_snapshot', 'unit_price_sen', 'quantity',
    'capacity_units_each', 'line_total_sen', 'sort_order',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function optionValues()
    {
        return $this->hasMany(OrderItemOptionValue::class);
    }
}
