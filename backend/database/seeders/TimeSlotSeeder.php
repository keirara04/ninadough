<?php

namespace Database\Seeders;

use App\Models\PreorderDate;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $openDate = PreorderDate::where('status', 'open')->first();

        if (! $openDate) {
            return;
        }

        TimeSlot::factory()->for($openDate)->create([
            'label' => '10am - 12pm',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'fulfilment_method' => 'both',
            'capacity_limit' => 10,
            'reserved_capacity' => 3,
        ]);

        TimeSlot::factory()->for($openDate)->create([
            'label' => '2pm - 4pm',
            'starts_at' => '14:00',
            'ends_at' => '16:00',
            'fulfilment_method' => 'pickup',
            'capacity_limit' => 6,
            'reserved_capacity' => 5,
        ]);

        TimeSlot::factory()->for($openDate)->create([
            'label' => '4pm - 6pm',
            'starts_at' => '16:00',
            'ends_at' => '18:00',
            'fulfilment_method' => 'delivery',
            'capacity_limit' => 5,
            'reserved_capacity' => 5,
        ]);
    }
}
