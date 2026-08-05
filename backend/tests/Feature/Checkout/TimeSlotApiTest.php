<?php

namespace Tests\Feature\Checkout;

use App\Models\PreorderDate;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSlotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_active_slots_for_a_date(): void
    {
        $preorderDate = PreorderDate::factory()->create();
        TimeSlot::factory()->for($preorderDate)->create(['label' => '10am-12pm', 'is_active' => true]);
        TimeSlot::factory()->for($preorderDate)->create(['label' => 'Inactive', 'is_active' => false]);

        $response = $this->getJson('/api/v1/time-slots?date='.$preorderDate->order_date->toDateString());

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.label', '10am-12pm');
    }

    public function test_filters_slots_by_fulfilment_method(): void
    {
        $preorderDate = PreorderDate::factory()->create();
        TimeSlot::factory()->for($preorderDate)->create(['fulfilment_method' => 'pickup']);
        TimeSlot::factory()->for($preorderDate)->create(['fulfilment_method' => 'delivery']);
        TimeSlot::factory()->for($preorderDate)->create(['fulfilment_method' => 'both']);

        $response = $this->getJson('/api/v1/time-slots?date='.$preorderDate->order_date->toDateString().'&fulfilment_method=pickup');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_returns_empty_for_date_with_no_slots(): void
    {
        $response = $this->getJson('/api/v1/time-slots?date='.now()->addDays(5)->toDateString());

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }
}
