<?php

namespace Tests\Feature\Schema;

use App\Models\ActivityLog;
use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_keeps_notification_log_row_when_order_is_deleted(): void
    {
        $log = NotificationLog::factory()->create();
        $log->order->delete();

        $this->assertNull($log->fresh()->order_id);
    }

    public function test_stores_before_after_jsonb_snapshots_with_no_updated_at(): void
    {
        $log = ActivityLog::factory()->create([
            'before' => ['is_active' => true],
            'after' => ['is_active' => false],
        ]);

        $this->assertFalse(Schema::hasColumn('activity_logs', 'updated_at'));
        $this->assertSame(['is_active' => true], $log->fresh()->before);
        $this->assertSame(['is_active' => false], $log->fresh()->after);
    }
}
