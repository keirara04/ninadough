<?php

namespace Tests\Feature\Checkout;

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_returns_zone_and_fee_for_served_postcode(): void
    {
        $zone = DeliveryZone::factory()->create(['name' => 'KL/PJ', 'delivery_fee_sen' => 800]);
        DeliveryZonePostcode::factory()->for($zone, 'deliveryZone')->create(['postcode' => '50000']);

        $response = $this->getJson('/api/v1/delivery-zones/lookup?postcode=50000');

        $response->assertOk();
        $response->assertJsonPath('data.name', 'KL/PJ');
        $response->assertJsonPath('data.delivery_fee_sen', 800);
    }

    public function test_lookup_returns_422_for_unserved_postcode(): void
    {
        $response = $this->getJson('/api/v1/delivery-zones/lookup?postcode=99999');

        $response->assertStatus(422);
    }

    public function test_lookup_requires_postcode(): void
    {
        $response = $this->getJson('/api/v1/delivery-zones/lookup');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('postcode');
    }
}
