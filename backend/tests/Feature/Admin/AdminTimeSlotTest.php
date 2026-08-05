<?php

namespace Tests\Feature\Admin;

use App\Models\PreorderDate;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTimeSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_time_slot(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $preorderDate = PreorderDate::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/preorder-dates/{$preorderDate->id}/time-slots", [
            'label' => '10am - 12pm',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'fulfilment_method' => 'both',
            'capacity_limit' => 5,
        ]);

        $response->assertCreated();
        $this->assertSame(1, TimeSlot::count());
    }

    public function test_staff_blocked_from_time_slot_crud(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $preorderDate = PreorderDate::factory()->create();
        Sanctum::actingAs($staff);

        $response = $this->postJson("/api/v1/admin/preorder-dates/{$preorderDate->id}/time-slots", [
            'label' => '10am - 12pm',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'fulfilment_method' => 'both',
            'capacity_limit' => 5,
        ]);

        $response->assertStatus(403);
    }

    public function test_destroy_deactivates_rather_than_deletes(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $preorderDate = PreorderDate::factory()->create();
        $slot = TimeSlot::factory()->for($preorderDate)->create(['is_active' => true]);
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/preorder-dates/{$preorderDate->id}/time-slots/{$slot->id}");

        $response->assertOk();
        $this->assertFalse($slot->fresh()->is_active);
    }

    public function test_rejects_time_slot_from_a_different_preorder_date(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $preorderDate = PreorderDate::factory()->create();
        $otherDate = PreorderDate::factory()->create();
        $slot = TimeSlot::factory()->for($otherDate)->create();
        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/v1/admin/preorder-dates/{$preorderDate->id}/time-slots/{$slot->id}", [
            'label' => 'Updated',
        ]);

        $response->assertStatus(404);
    }
}
