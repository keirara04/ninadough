<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PreorderDate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'order_number' => 'ND-'.$this->faker->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'preorder_date_id' => PreorderDate::factory(),
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'customer_name_snapshot' => $this->faker->name(),
            'customer_phone_snapshot' => '+60123456789',
            'subtotal_sen' => 3600,
            'delivery_fee_sen' => 0,
            'discount_sen' => 0,
            'total_sen' => 3600,
            'total_capacity_units' => 1,
            'status' => 'awaiting_payment',
            'payment_status' => 'awaiting_payment',
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
