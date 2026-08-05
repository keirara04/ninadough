<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCheckoutDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function basePayload(array $overrides = []): array
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

    public function test_website_checkout_requires_email(): void
    {
        $payload = $this->basePayload();
        $payload['customer'] = ['name' => 'Test Customer', 'phone_e164' => '+60123456789'];

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('customer.email');
    }

    public function test_whatsapp_checkout_does_not_require_email(): void
    {
        $payload = $this->basePayload(['checkout_channel' => 'whatsapp']);
        $payload['customer'] = ['name' => 'Test Customer', 'phone_e164' => '+60123456789'];

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertCreated();
    }

    public function test_persists_notes_card_message_allergies_and_hide_price_flag(): void
    {
        $payload = $this->basePayload([
            'notes' => 'Please deliver after 3pm.',
            'card_message' => 'Happy Birthday!',
            'allergies_note' => 'Nut allergy.',
            'hide_price_on_package' => true,
        ]);

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertCreated();
        $order = Order::first();
        $this->assertSame('Please deliver after 3pm.', $order->notes);
        $this->assertSame('Happy Birthday!', $order->card_message);
        $this->assertSame('Nut allergy.', $order->allergies_note);
        $this->assertTrue($order->hide_price_on_package);
    }

    public function test_extra_details_are_optional(): void
    {
        $response = $this->postJson('/api/v1/orders', $this->basePayload());

        $response->assertCreated();
        $order = Order::first();
        $this->assertNull($order->notes);
        $this->assertNull($order->card_message);
        $this->assertNull($order->allergies_note);
        $this->assertFalse($order->hide_price_on_package);
    }

    public function test_payment_method_only_accepts_bank_transfer(): void
    {
        $payload = $this->basePayload(['payment_method' => 'fpx']);

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('payment_method');
    }

    public function test_payment_method_accepts_bank_transfer(): void
    {
        $payload = $this->basePayload(['payment_method' => 'bank_transfer']);

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertCreated();
        $this->assertSame('bank_transfer', Order::first()->payment_method);
    }
}
