<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OwnerUserSeeder::class,
            BusinessSettingsSeeder::class,
            ProductSeeder::class,
            ProductCategorySeeder::class,
            MockProductSeeder::class,
            PreorderDateSeeder::class,
            TimeSlotSeeder::class,
            DeliveryZoneSeeder::class,
            OrderDemoSeeder::class,
        ]);
    }
}
