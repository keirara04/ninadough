<?php

namespace Tests\Feature\Orders;

use App\Actions\Orders\ReleaseOrderCapacityAction;
use App\Models\Order;
use App\Models\PreorderDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseOrderCapacityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_releases_capacity_and_transitions_status(): void
    {
        $date = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 4]);
        $order = Order::factory()->create([
            'preorder_date_id' => $date->id,
            'total_capacity_units' => 2,
            'status' => 'awaiting_payment',
            'capacity_released_at' => null,
        ]);

        $updated = (new ReleaseOrderCapacityAction)->execute($order->id, 'cancelled');

        $this->assertSame('cancelled', $updated->status);
        $this->assertNotNull($updated->capacity_released_at);
        $this->assertSame(2, $date->fresh()->reserved_capacity);
    }

    public function test_flips_full_date_back_to_open_when_room_is_freed(): void
    {
        $date = PreorderDate::factory()->create(['capacity_limit' => 5, 'reserved_capacity' => 5, 'status' => 'full']);
        $order = Order::factory()->create([
            'preorder_date_id' => $date->id,
            'total_capacity_units' => 1,
            'status' => 'awaiting_payment',
        ]);

        (new ReleaseOrderCapacityAction)->execute($order->id, 'rejected');

        $this->assertSame('open', $date->fresh()->status);
        $this->assertSame(4, $date->fresh()->reserved_capacity);
    }

    public function test_does_not_double_release_capacity_on_repeated_calls(): void
    {
        $date = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 4]);
        $order = Order::factory()->create([
            'preorder_date_id' => $date->id,
            'total_capacity_units' => 2,
            'status' => 'awaiting_payment',
        ]);

        (new ReleaseOrderCapacityAction)->execute($order->id, 'cancelled');
        (new ReleaseOrderCapacityAction)->execute($order->id, 'cancelled');

        $this->assertSame(2, $date->fresh()->reserved_capacity);
    }

    public function test_records_status_event_with_from_and_to_status(): void
    {
        $date = PreorderDate::factory()->create();
        $order = Order::factory()->create(['preorder_date_id' => $date->id, 'status' => 'awaiting_payment']);

        (new ReleaseOrderCapacityAction)->execute($order->id, 'rejected');

        $event = $order->statusEvents()->latest('created_at')->first();
        $this->assertSame('awaiting_payment', $event->from_status);
        $this->assertSame('rejected', $event->to_status);
    }
}
