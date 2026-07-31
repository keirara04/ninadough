<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusEventFactory extends Factory
{
    protected $model = OrderStatusEvent::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'to_status' => 'awaiting_payment',
            'actor_type' => 'system',
        ];
    }
}
