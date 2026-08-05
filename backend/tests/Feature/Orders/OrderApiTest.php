<?php

namespace Tests\Feature\Orders;

use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderPayload(array $overrides = []): array
    {
        $product = Product::factory()->create(['base_price_sen' => 4000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 0, 'capacity_units' => 1]);
        $preorderDate = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 0]);

        return array_merge([
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'idempotency_key' => (string) Str::uuid(),
            'customer' => ['name' => 'Test Customer', 'phone_e164' => '+60123456789', 'email' => 'test@example.com'],
        ], $overrides);
    }

    public function test_creates_website_order_via_api(): void
    {
        $payload = $this->makeOrderPayload();

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'awaiting_payment');
        $response->assertJsonPath('data.total_sen', 4000);
        $this->assertSame(1, Order::count());
    }

    public function test_creates_whatsapp_order_with_prefilled_link(): void
    {
        BusinessSetting::factory()->create(['key' => 'whatsapp_number', 'value' => '+60123450000']);
        $payload = $this->makeOrderPayload(['checkout_channel' => 'whatsapp']);

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'whatsapp_pending');
        $response->assertJsonPath('data.whatsapp_url', fn ($url) => str_starts_with($url, 'https://wa.me/60123450000?text='));
    }

    public function test_rejects_delivery_order_missing_delivery_address(): void
    {
        $payload = $this->makeOrderPayload(['fulfilment_method' => 'delivery']);

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['delivery_address.line_1']);
    }

    public function test_returns_409_when_date_is_full(): void
    {
        $payload = $this->makeOrderPayload();
        PreorderDate::whereDate('order_date', $payload['preorder_date'])->update(['reserved_capacity' => 10, 'capacity_limit' => 10]);

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertStatus(409);
    }

    public function test_returns_422_when_date_is_past_cutoff(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create(['cutoff_at' => now()->subMinute()]);

        $response = $this->postJson('/api/v1/orders', $this->makeOrderPayload([
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
        ]));

        $response->assertStatus(422);
    }

    public function test_duplicate_idempotency_key_returns_same_order(): void
    {
        $payload = $this->makeOrderPayload();

        $first = $this->postJson('/api/v1/orders', $payload)->json('data.id');
        $second = $this->postJson('/api/v1/orders', $payload)->json('data.id');

        $this->assertSame($first, $second);
        $this->assertSame(1, Order::count());
    }

    public function test_order_status_lookup_requires_valid_signature(): void
    {
        $order = Order::factory()->create();

        $response = $this->getJson("/api/v1/orders/{$order->order_number}/status");
        $response->assertForbidden();

        $signedUrl = URL::temporarySignedRoute('orders.status', now()->addMinutes(5), ['reference' => $order->order_number]);
        $signedResponse = $this->getJson($signedUrl);

        $signedResponse->assertOk();
        $signedResponse->assertJsonPath('data.order_number', $order->order_number);
    }
}
