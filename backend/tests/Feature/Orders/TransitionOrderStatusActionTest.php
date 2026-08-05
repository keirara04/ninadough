<?php

namespace Tests\Feature\Orders;

use App\Actions\Orders\TransitionOrderStatusAction;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PreorderDate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransitionOrderStatusActionTest extends TestCase
{
    use RefreshDatabase;

    private function orderWithReservedVariant(string $status = 'payment_submitted', int $quantity = 2): Order
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'reserved_quantity' => $quantity]);
        $date = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 3]);
        $order = Order::factory()->create([
            'status' => $status,
            'preorder_date_id' => $date->id,
            'total_capacity_units' => 2,
            'payment_status' => 'submitted',
        ]);
        OrderItem::factory()->for($order)->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'capacity_units_each' => 1,
        ]);

        return $order;
    }

    public function test_valid_transition_succeeds_with_deduct_effect(): void
    {
        $order = $this->orderWithReservedVariant('payment_submitted', 2);
        $variantId = $order->items->first()->product_variant_id;

        $updated = (new TransitionOrderStatusAction)->execute($order->id, 'payment_confirmed', 'user', User::factory()->create());

        $this->assertSame('payment_confirmed', $updated->status);
        $this->assertSame('paid', $updated->payment_status);
        $this->assertNotNull($updated->paid_at);

        $variant = ProductVariant::find($variantId);
        $this->assertSame(8, $variant->stock_quantity);
        $this->assertSame(0, $variant->reserved_quantity);
        $this->assertSame(1, $order->statusEvents()->count());
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $order = Order::factory()->create(['status' => 'completed']);

        $this->expectException(InvalidOrderTransitionException::class);
        (new TransitionOrderStatusAction)->execute($order->id, 'payment_submitted', 'user', User::factory()->create());
    }

    public function test_completed_to_cancelled_is_rejected_no_wildcard_path(): void
    {
        $order = Order::factory()->create(['status' => 'completed']);

        $this->expectException(InvalidOrderTransitionException::class);
        (new TransitionOrderStatusAction)->execute($order->id, 'cancelled', 'user', User::factory()->create(['role' => 'owner']));
    }

    public function test_reject_requires_a_message(): void
    {
        $order = $this->orderWithReservedVariant('payment_submitted');

        $this->expectException(ValidationException::class);
        (new TransitionOrderStatusAction)->execute($order->id, 'rejected', 'user', User::factory()->create());
    }

    public function test_reject_releases_stock_and_capacity_and_stores_message(): void
    {
        $order = $this->orderWithReservedVariant('payment_submitted', 2);
        $variantId = $order->items->first()->product_variant_id;
        $dateId = $order->preorder_date_id;

        $updated = (new TransitionOrderStatusAction)->execute(
            $order->id, 'rejected', 'user', User::factory()->create(), 'Blurry receipt, please re-upload.'
        );

        $this->assertSame('rejected', $updated->status);
        $this->assertSame('Blurry receipt, please re-upload.', $updated->rejection_message);
        $this->assertSame(0, ProductVariant::find($variantId)->reserved_quantity);
        $this->assertSame(1, PreorderDate::find($dateId)->reserved_capacity);
    }

    public function test_post_payment_cancel_requires_owner_role(): void
    {
        $order = $this->orderWithReservedVariant('payment_confirmed', 2);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->expectException(AuthorizationException::class);
        (new TransitionOrderStatusAction)->execute($order->id, 'cancelled', 'user', $staff);
    }

    public function test_post_payment_cancel_by_owner_sets_refund_required_without_releasing_stock(): void
    {
        $order = $this->orderWithReservedVariant('payment_confirmed', 2);
        $variantId = $order->items->first()->product_variant_id;
        $owner = User::factory()->create(['role' => 'owner']);

        $updated = (new TransitionOrderStatusAction)->execute($order->id, 'cancelled', 'user', $owner);

        $this->assertSame('cancelled', $updated->status);
        $this->assertTrue($updated->refund_required);
        // reserved_quantity was already 2 from the fixture (simulating a post-deduct order) — cancel must not touch it.
        $this->assertSame(2, ProductVariant::find($variantId)->reserved_quantity);
    }

    public function test_scheduled_expiry_transition_rejected_for_non_system_actor(): void
    {
        $order = Order::factory()->create(['status' => 'awaiting_payment']);

        $this->expectException(InvalidOrderTransitionException::class);
        (new TransitionOrderStatusAction)->execute($order->id, 'expired', 'user', User::factory()->create());
    }

    public function test_scheduled_expiry_transition_allowed_for_system_actor(): void
    {
        $order = $this->orderWithReservedVariant('awaiting_payment', 2);

        $updated = (new TransitionOrderStatusAction)->execute($order->id, 'expired', 'system');

        $this->assertSame('expired', $updated->status);
    }

    public function test_repeated_transition_to_same_status_is_idempotent_and_does_not_double_deduct(): void
    {
        $order = $this->orderWithReservedVariant('payment_submitted', 2);
        $variantId = $order->items->first()->product_variant_id;
        $owner = User::factory()->create(['role' => 'owner']);

        (new TransitionOrderStatusAction)->execute($order->id, 'payment_confirmed', 'user', $owner);
        (new TransitionOrderStatusAction)->execute($order->id, 'payment_confirmed', 'user', $owner);

        $this->assertSame(8, ProductVariant::find($variantId)->stock_quantity);
        $this->assertSame(1, $order->fresh()->statusEvents()->count());
    }
}
