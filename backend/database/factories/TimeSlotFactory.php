<?php

namespace Database\Factories;

use App\Models\PreorderDate;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition(): array
    {
        return [
            'preorder_date_id' => PreorderDate::factory(),
            'label' => '10am - 12pm',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'fulfilment_method' => 'both',
            'capacity_limit' => 10,
            'reserved_capacity' => 0,
            'is_active' => true,
        ];
    }
}
