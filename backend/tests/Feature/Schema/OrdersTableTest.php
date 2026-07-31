<?php

namespace Tests\Feature\Schema;

use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\PreorderDate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdersTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderAttributes(array $overrides = []): array
    {
        return array_merge([
            'ulid' => (string) Str::ulid(),
            'order_number' => 'ND-'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory()->create()->id,
            'preorder_date_id' => PreorderDate::factory()->create()->id,
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'customer_name_snapshot' => 'Test Customer',
            'customer_phone_snapshot' => '+60123456789',
            'subtotal_sen' => 3600,
            'delivery_fee_sen' => 0,
            'discount_sen' => 0,
            'total_sen' => 3600,
            'total_capacity_units' => 1,
            'status' => 'awaiting_payment',
            'payment_status' => 'awaiting_payment',
            'idempotency_key' => (string) Str::uuid(),
        ], $overrides);
    }

    public function test_creates_valid_pickup_order(): void
    {
        $order = Order::create($this->makeOrderAttributes());

        $this->assertTrue($order->exists);
    }

    public function test_rejects_pickup_order_with_delivery_address(): void
    {
        $this->expectException(QueryException::class);
        Order::create($this->makeOrderAttributes([
            'delivery_address' => ['line_1' => 'x'],
        ]));
    }

    public function test_rejects_delivery_order_missing_address_and_zone(): void
    {
        $this->expectException(QueryException::class);
        Order::create($this->makeOrderAttributes([
            'fulfilment_method' => 'delivery',
        ]));
    }

    public function test_accepts_delivery_order_with_address_and_zone(): void
    {
        $zone = DeliveryZone::factory()->create();

        $order = Order::create($this->makeOrderAttributes([
            'fulfilment_method' => 'delivery',
            'delivery_zone_id' => $zone->id,
            'delivery_address' => ['line_1' => 'Jalan Test', 'postcode' => '43000'],
        ]));

        $this->assertTrue($order->exists);
    }

    public function test_rejects_whatsapp_pending_order_with_no_expires_at(): void
    {
        $this->expectException(QueryException::class);
        Order::create($this->makeOrderAttributes([
            'checkout_channel' => 'whatsapp',
            'status' => 'whatsapp_pending',
        ]));
    }

    public function test_rejects_duplicate_idempotency_key(): void
    {
        $attrs = $this->makeOrderAttributes();
        Order::create($attrs);

        $attrs['ulid'] = (string) Str::ulid();
        $attrs['order_number'] = 'ND-'.fake()->unique()->numerify('######');

        $this->expectException(QueryException::class);
        Order::create($attrs);
    }
}
