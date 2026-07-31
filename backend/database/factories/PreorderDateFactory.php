<?php

namespace Database\Factories;

use App\Models\PreorderDate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreorderDateFactory extends Factory
{
    protected $model = PreorderDate::class;

    public function definition(): array
    {
        $orderDate = $this->faker->unique()->dateTimeBetween('+1 day', '+60 days');

        return [
            'order_date' => $orderDate->format('Y-m-d'),
            'cutoff_at' => (clone $orderDate)->modify('-1 day 18:00'),
            'capacity_limit' => 20,
            'reserved_capacity' => 0,
            'pickup_enabled' => true,
            'delivery_enabled' => true,
            'status' => 'open',
        ];
    }
}
