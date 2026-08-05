<?php

namespace Tests\Feature\Inventory;

use App\Actions\Checkout\CreateOrderAction;
use App\Exceptions\InsufficientStockException;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockReservationTest extends TestCase
{
    use RefreshDatabase;

    private function makePayload(ProductVariant $variant, PreorderDate $preorderDate, int $quantity = 1): array
    {
        return [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => $quantity]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'checkout_channel' => 'website',
            'fulfilment_method' => 'pickup',
            'idempotency_key' => (string) Str::uuid(),
            'customer' => ['name' => 'Test Customer', 'phone_e164' => '+60123456789'],
        ];
    }

    public function test_order_creation_reserves_stock(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 5, 'reserved_quantity' => 0]);
        $preorderDate = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 0]);

        (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, 3));

        $this->assertSame(3, $variant->fresh()->reserved_quantity);
        $this->assertSame(5, $variant->fresh()->stock_quantity);
    }

    public function test_last_unit_two_concurrent_creations_one_wins_one_fails(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 1, 'reserved_quantity' => 0]);
        $preorderDate = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 0]);

        (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, 1));

        $this->expectException(InsufficientStockException::class);
        try {
            (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, 1));
        } finally {
            $this->assertSame(1, $variant->fresh()->reserved_quantity);
        }
    }

    public function test_order_creation_fails_when_stock_insufficient_and_nothing_persists(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 2, 'reserved_quantity' => 0]);
        $preorderDate = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 0]);

        $this->expectException(InsufficientStockException::class);
        try {
            (new CreateOrderAction)->execute($this->makePayload($variant, $preorderDate, 3));
        } finally {
            $this->assertSame(0, $variant->fresh()->reserved_quantity);
            $this->assertSame(0, \App\Models\Order::count());
        }
    }
}
