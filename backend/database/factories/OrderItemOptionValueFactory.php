<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemOptionValueFactory extends Factory
{
    protected $model = OrderItemOptionValue::class;

    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'option_group_name_snapshot' => 'Size',
            'option_value_name_snapshot' => 'Medium',
            'sort_order' => 0,
        ];
    }
}
