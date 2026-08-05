<?php

namespace Tests\Feature\Notifications;

use App\Actions\Checkout\CreateOrderAction;
use App\Actions\Orders\TransitionOrderStatusAction;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusUpdateMail;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makePayload(array $overrides = []): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();

        return array_merge([
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'idempotency_key' => (string) Str::uuid(),
            'customer' => ['name' => 'Test Customer', 'phone_e164' => '+60123456789', 'email' => 'test@example.com'],
        ], $overrides);
    }

    public function test_order_creation_queues_confirmation_email(): void
    {
        Mail::fake();

        $order = (new CreateOrderAction)->execute($this->makePayload());

        Mail::assertQueued(OrderConfirmationMail::class, fn ($mail) => $mail->order->id === $order->id);
        $this->assertSame(1, NotificationLog::where('template_key', 'order_confirmation')->count());
    }

    public function test_whatsapp_order_without_email_does_not_queue_mail(): void
    {
        Mail::fake();

        (new CreateOrderAction)->execute($this->makePayload([
            'checkout_channel' => 'whatsapp',
            'customer' => ['name' => 'Test Customer', 'phone_e164' => '+60123456789'],
        ]));

        Mail::assertNothingQueued();
    }

    public function test_status_transition_to_preparing_queues_status_update_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => 'payment_confirmed', 'customer_email_snapshot' => 'test@example.com']);

        (new TransitionOrderStatusAction)->execute(orderId: $order->id, toStatus: 'preparing', actorType: 'system');

        Mail::assertQueued(OrderStatusUpdateMail::class, fn ($mail) => $mail->statusHeadline === "We're preparing your order");
    }

    public function test_idempotent_no_op_transition_does_not_queue_duplicate_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => 'preparing', 'customer_email_snapshot' => 'test@example.com']);

        (new TransitionOrderStatusAction)->execute(orderId: $order->id, toStatus: 'preparing', actorType: 'system');

        Mail::assertNothingQueued();
    }

    public function test_transition_without_customer_email_does_not_queue_mail(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => 'payment_confirmed', 'customer_email_snapshot' => null]);

        (new TransitionOrderStatusAction)->execute(orderId: $order->id, toStatus: 'preparing', actorType: 'system');

        Mail::assertNothingQueued();
    }

    public function test_transition_to_non_notifying_status_does_not_queue_mail(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => 'ready_for_pickup', 'customer_email_snapshot' => 'test@example.com']);

        (new TransitionOrderStatusAction)->execute(orderId: $order->id, toStatus: 'completed', actorType: 'system');

        Mail::assertNothingQueued();
    }
}
