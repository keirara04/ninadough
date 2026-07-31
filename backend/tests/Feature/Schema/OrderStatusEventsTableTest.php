<?php

namespace Tests\Feature\Schema;

use App\Models\OrderStatusEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderStatusEventsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_event_with_no_updated_at_column(): void
    {
        $event = OrderStatusEvent::factory()->create();

        $this->assertFalse(Schema::hasColumn('order_status_events', 'updated_at'));
        $this->assertNotNull($event->created_at);
    }

    public function test_rejects_actor_type_outside_user_or_system(): void
    {
        $this->expectException(QueryException::class);
        OrderStatusEvent::factory()->create(['actor_type' => 'robot']);
    }
}
