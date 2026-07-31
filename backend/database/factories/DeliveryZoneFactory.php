<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryZoneFactory extends Factory
{
    protected $model = DeliveryZone::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->city(),
            'delivery_fee_sen' => 800,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
