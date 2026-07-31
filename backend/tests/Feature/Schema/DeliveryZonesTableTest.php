<?php

namespace Tests\Feature\Schema;

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZonesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_negative_delivery_fee_sen(): void
    {
        $this->expectException(QueryException::class);
        DeliveryZone::factory()->create(['delivery_fee_sen' => -1]);
    }

    public function test_rejects_same_postcode_assigned_to_two_zones(): void
    {
        DeliveryZonePostcode::factory()->create(['postcode' => '43000']);

        $this->expectException(QueryException::class);
        DeliveryZonePostcode::factory()->create(['postcode' => '43000']);
    }
}
