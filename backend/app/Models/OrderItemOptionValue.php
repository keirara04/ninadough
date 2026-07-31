<?php

namespace App\Models;

use Database\Factories\OrderItemOptionValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_item_id', 'option_group_name_snapshot', 'option_value_name_snapshot', 'sort_order'])]
class OrderItemOptionValue extends Model
{
    /** @use HasFactory<OrderItemOptionValueFactory> */
    use HasFactory;

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
