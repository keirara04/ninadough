<?php

namespace Tests\Feature\Admin;

use App\Models\PreorderDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPreorderDateCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_preorder_date(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/admin/preorder-dates', [
            'order_date' => now()->addDays(3)->toDateString(),
            'cutoff_at' => now()->addDays(2)->toIso8601String(),
            'capacity_limit' => 10,
        ]);

        $response->assertCreated();
        $this->assertSame(1, PreorderDate::count());
        $response->assertJsonPath('data.status', 'open');
    }

    public function test_owner_can_update_preorder_date(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $date = PreorderDate::factory()->create(['capacity_limit' => 5]);
        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/v1/admin/preorder-dates/{$date->id}", [
            'capacity_limit' => 20,
        ]);

        $response->assertOk();
        $this->assertSame(20, $date->fresh()->capacity_limit);
    }

    public function test_staff_blocked_from_preorder_date_crud(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/admin/preorder-dates', [
            'order_date' => now()->addDays(3)->toDateString(),
            'cutoff_at' => now()->addDays(2)->toIso8601String(),
            'capacity_limit' => 10,
        ]);

        $response->assertStatus(403);
    }
}
