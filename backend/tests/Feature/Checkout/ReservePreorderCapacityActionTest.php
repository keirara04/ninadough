<?php

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\ReservePreorderCapacityAction;
use App\Exceptions\PreorderDateFullException;
use App\Exceptions\PreorderDateUnavailableException;
use App\Models\PreorderDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservePreorderCapacityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserves_capacity_and_increments_reserved_capacity(): void
    {
        $date = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 3]);

        $updated = (new ReservePreorderCapacityAction)->execute($date->id, 2, 'pickup');

        $this->assertSame(5, $updated->reserved_capacity);
        $this->assertSame('open', $updated->status);
    }

    public function test_flips_status_to_full_when_capacity_reaches_limit(): void
    {
        $date = PreorderDate::factory()->create(['capacity_limit' => 5, 'reserved_capacity' => 4]);

        $updated = (new ReservePreorderCapacityAction)->execute($date->id, 1, 'pickup');

        $this->assertSame(5, $updated->reserved_capacity);
        $this->assertSame('full', $updated->status);
    }

    public function test_rejects_reservation_exceeding_remaining_capacity(): void
    {
        $date = PreorderDate::factory()->create(['capacity_limit' => 5, 'reserved_capacity' => 4]);

        $this->expectException(PreorderDateFullException::class);
        (new ReservePreorderCapacityAction)->execute($date->id, 2, 'pickup');
    }

    public function test_rejects_closed_date(): void
    {
        $date = PreorderDate::factory()->create(['status' => 'closed']);

        $this->expectException(PreorderDateUnavailableException::class);
        (new ReservePreorderCapacityAction)->execute($date->id, 1, 'pickup');
    }

    public function test_rejects_date_past_cutoff(): void
    {
        $date = PreorderDate::factory()->create(['cutoff_at' => now()->subMinute()]);

        $this->expectException(PreorderDateUnavailableException::class);
        (new ReservePreorderCapacityAction)->execute($date->id, 1, 'pickup');
    }

    public function test_rejects_delivery_when_delivery_disabled(): void
    {
        $date = PreorderDate::factory()->create(['delivery_enabled' => false]);

        $this->expectException(PreorderDateUnavailableException::class);
        (new ReservePreorderCapacityAction)->execute($date->id, 1, 'delivery');
    }

    public function test_rejects_pickup_when_pickup_disabled(): void
    {
        $date = PreorderDate::factory()->create(['pickup_enabled' => false]);

        $this->expectException(PreorderDateUnavailableException::class);
        (new ReservePreorderCapacityAction)->execute($date->id, 1, 'pickup');
    }
}
