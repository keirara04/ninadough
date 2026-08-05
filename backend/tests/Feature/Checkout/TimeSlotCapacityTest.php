<?php

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\CreateOrderAction;
use App\Actions\Orders\ReleaseOrderCapacityAction;
use App\Exceptions\TimeSlotFullException;
use App\Exceptions\TimeSlotUnavailableException;
use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TimeSlotCapacityTest extends TestCase
{
    use RefreshDatabase;

    private function makePayload(ProductVariant $variant, PreorderDate $preorderDate, ?TimeSlot $timeSlot = null): array
    {
        return [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'time_slot_id' => $timeSlot?->id,
            'idempotency_key' => (string) Str::uuid(),
            'customer' => ['name' => 'Test Customer', 'phone_e164' => '+60123456789', 'email' => 'test@example.com'],
        ];
    }

    public function test_order_creation_reserves_time_slot_capacity(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();
        $slot = TimeSlot::factory()->for($preorderDate)->create(['capacity_limit' => 2, 'reserved_capacity' => 0]);

        (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, $slot));

        $this->assertSame(1, $slot->fresh()->reserved_capacity);
    }

    public function test_rejects_order_when_time_slot_is_full(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();
        $slot = TimeSlot::factory()->for($preorderDate)->create(['capacity_limit' => 1, 'reserved_capacity' => 1]);

        $this->expectException(TimeSlotFullException::class);
        try {
            (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, $slot));
        } finally {
            $this->assertSame(0, Order::count());
        }
    }

    public function test_rejects_time_slot_belonging_to_a_different_preorder_date(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();
        $otherDate = PreorderDate::factory()->create();
        $slot = TimeSlot::factory()->for($otherDate)->create();

        $this->expectException(TimeSlotUnavailableException::class);
        (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, $slot));
    }

    public function test_rejects_time_slot_not_matching_fulfilment_method(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();
        $slot = TimeSlot::factory()->for($preorderDate)->create(['fulfilment_method' => 'delivery']);

        $this->expectException(TimeSlotUnavailableException::class);
        (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, $slot));
    }

    public function test_releasing_order_releases_time_slot_capacity(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();
        $slot = TimeSlot::factory()->for($preorderDate)->create(['capacity_limit' => 1, 'reserved_capacity' => 0]);

        $order = (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, $slot));
        $this->assertSame(1, $slot->fresh()->reserved_capacity);

        (new ReleaseOrderCapacityAction)->execute($order->id, 'expired');

        $this->assertSame(0, $slot->fresh()->reserved_capacity);
    }
}
