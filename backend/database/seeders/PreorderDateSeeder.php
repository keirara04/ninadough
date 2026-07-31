<?php

namespace Database\Seeders;

use App\Models\PreorderDate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PreorderDateSeeder extends Seeder
{
    public function run(): void
    {
        PreorderDate::factory()->create([
            'order_date' => Carbon::now('Asia/Kuala_Lumpur')->addDays(2)->toDateString(),
            'status' => 'open',
            'capacity_limit' => 20,
            'reserved_capacity' => 5,
        ]);

        PreorderDate::factory()->create([
            'order_date' => Carbon::now('Asia/Kuala_Lumpur')->addDays(3)->toDateString(),
            'status' => 'full',
            'capacity_limit' => 10,
            'reserved_capacity' => 10,
        ]);

        PreorderDate::factory()->create([
            'order_date' => Carbon::now('Asia/Kuala_Lumpur')->addDays(4)->toDateString(),
            'status' => 'closed',
            'capacity_limit' => 15,
            'reserved_capacity' => 0,
        ]);
    }
}
