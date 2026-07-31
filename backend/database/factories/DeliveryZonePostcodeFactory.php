<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryZonePostcodeFactory extends Factory
{
    protected $model = DeliveryZonePostcode::class;

    public function definition(): array
    {
        return [
            'delivery_zone_id' => DeliveryZone::factory(),
            'postcode' => $this->faker->unique()->numerify('#####'),
        ];
    }
}
