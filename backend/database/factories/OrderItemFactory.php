<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name_snapshot' => $this->faker->words(2, true),
            'unit_price_sen' => 3600,
            'quantity' => 1,
            'capacity_units_each' => 1,
            'line_total_sen' => 3600,
            'sort_order' => 0,
        ];
    }
}
