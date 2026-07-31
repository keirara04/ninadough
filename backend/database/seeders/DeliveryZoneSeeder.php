<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zone = DeliveryZone::factory()->create([
            'name' => 'Klang Valley',
            'delivery_fee_sen' => 800,
        ]);

        foreach (['40000', '40100', '43000'] as $postcode) {
            DeliveryZonePostcode::factory()->for($zone)->create(['postcode' => $postcode]);
        }
    }
}
