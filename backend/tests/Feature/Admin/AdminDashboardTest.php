<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_dashboard_metrics(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Order::factory()->create(['status' => 'payment_submitted']);
        Order::factory()->create(['status' => 'payment_confirmed', 'payment_status' => 'paid', 'paid_at' => now(), 'total_sen' => 5000]);
        PreorderDate::factory()->create(['order_date' => now()->addDay()->toDateString()]);
        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertOk();
        $response->assertJsonPath('data.pending_payment_review_count', 1);
        $response->assertJsonPath('data.revenue_sen.today', 5000);
        $response->assertJsonStructure(['data' => [
            'todays_order_count', 'pending_payment_review_count', 'revenue_sen',
            'upcoming_preorder_dates', 'orders_needing_attention',
        ]]);
    }

    public function test_staff_blocked_from_dashboard(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    }
}
