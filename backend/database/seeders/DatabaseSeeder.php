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
            PreorderDateSeeder::class,
            DeliveryZoneSeeder::class,
            OrderDemoSeeder::class,
        ]);
    }
}
