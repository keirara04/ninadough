<?php

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\CreateOrderAction;
use App\Exceptions\PreorderDateFullException;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private function makePayload(array $overrides = []): array
    {
        $product = Product::factory()->create(['base_price_sen' => 3600]);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 0, 'capacity_units' => 1]);
        $preorderDate = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 0]);

        return array_merge([
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'idempotency_key' => (string) Str::uuid(),
            'customer' => ['name' => 'Test Customer', 'phone_e164' => '+60123456789'],
        ], $overrides);
    }

    private function preorderDateFor(array $payload): PreorderDate
    {
        return PreorderDate::whereDate('order_date', $payload['preorder_date'])->firstOrFail();
    }

    public function test_creates_website_order_with_correct_totals_and_reserves_capacity(): void
    {
        $payload = $this->makePayload();

        $order = (new CreateOrderAction)->execute($payload);

        $this->assertSame('awaiting_payment', $order->status);
        $this->assertSame('awaiting_payment', $order->payment_status);
        $this->assertSame(3600, $order->total_sen);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(1, $this->preorderDateFor($payload)->reserved_capacity);
        $this->assertSame(1, $order->statusEvents()->count());
    }

    public function test_creates_whatsapp_order_with_expiry(): void
    {
        BusinessSetting::factory()->create(['key' => 'default_whatsapp_reservation_minutes', 'value' => 20]);
        $payload = $this->makePayload(['checkout_channel' => 'whatsapp']);

        $order = (new CreateOrderAction)->execute($payload);

        $this->assertSame('whatsapp_pending', $order->status);
        $this->assertSame('not_required', $order->payment_status);
        $this->assertNotNull($order->expires_at);
        $this->assertTrue($order->expires_at->greaterThan(now()));
    }

    public function test_duplicate_idempotency_key_returns_same_order_without_creating_another(): void
    {
        $payload = $this->makePayload();

        $first = (new CreateOrderAction)->execute($payload);
        $second = (new CreateOrderAction)->execute($payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, $this->preorderDateFor($payload)->reserved_capacity);
    }

    public function test_throws_and_creates_nothing_when_date_is_full(): void
    {
        $payload = $this->makePayload();
        $this->preorderDateFor($payload)->update(['reserved_capacity' => 10, 'capacity_limit' => 10]);

        $this->expectException(PreorderDateFullException::class);

        try {
            (new CreateOrderAction)->execute($payload);
        } finally {
            $this->assertSame(0, Order::count());
        }
    }
}
