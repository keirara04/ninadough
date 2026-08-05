<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminRoleRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_blocked_from_product_crud(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/v1/admin/products', ['name' => 'New Cake']);

        $response->assertStatus(403);
    }

    public function test_staff_blocked_from_order_status_transition(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $order = Order::factory()->create(['status' => 'preparing']);
        Sanctum::actingAs($staff);

        $response = $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'ready_for_pickup']);

        $response->assertStatus(403);
    }

    public function test_staff_can_list_and_view_orders(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $order = Order::factory()->create();
        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/admin/orders')->assertOk();
        $this->getJson("/api/v1/admin/orders/{$order->id}")->assertOk();
    }

    public function test_staff_can_review_payment(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $order = Order::factory()->create(['status' => 'payment_submitted', 'payment_status' => 'submitted']);
        $payment = Payment::factory()->for($order)->create(['status' => 'submitted']);
        Sanctum::actingAs($staff);

        $response = $this->patchJson("/api/v1/admin/payments/{$payment->id}/review", ['action' => 'approve']);

        $response->assertOk();
    }

    public function test_owner_unrestricted(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/admin/categories', ['name' => 'Cakes', 'slug' => 'cakes']);

        $response->assertStatus(201);
    }
}
