<?php

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\PriceCartAction;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PriceCartActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prices_cart_using_server_side_product_and_variant_prices(): void
    {
        $product = Product::factory()->create(['base_price_sen' => 8000, 'default_capacity_units' => 1]);
        $group = ProductOptionGroup::factory()->for($product)->create(['name' => 'Size']);
        $value = ProductOptionValue::factory()->for($group, 'productOptionGroup')->create(['name' => 'Large']);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 2000, 'capacity_units' => 2]);
        $variant->optionValues()->attach($value);

        $preorderDate = PreorderDate::factory()->create(['capacity_limit' => 10, 'reserved_capacity' => 3]);

        $quote = (new PriceCartAction)->execute(
            items: [['product_variant_id' => $variant->id, 'quantity' => 2]],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'pickup',
        );

        $this->assertSame(10000, $quote->lines[0]['unit_price_sen']);
        $this->assertSame(20000, $quote->subtotalSen);
        $this->assertSame(0, $quote->deliveryFeeSen);
        $this->assertSame(20000, $quote->totalSen);
        $this->assertSame(4, $quote->totalCapacityUnits);
        $this->assertSame(7, $quote->remainingCapacity);
        $this->assertSame('Large', $quote->lines[0]['option_values'][0]['option_value_name']);
    }

    public function test_ignores_client_supplied_prices_and_always_recomputes(): void
    {
        $product = Product::factory()->create(['base_price_sen' => 5000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 0]);
        $preorderDate = PreorderDate::factory()->create();

        $quote = (new PriceCartAction)->execute(
            items: [['product_variant_id' => $variant->id, 'quantity' => 1]],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'pickup',
        );

        $this->assertSame(5000, $quote->lines[0]['unit_price_sen']);
    }

    public function test_rejects_inactive_variant(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['is_active' => false]);
        $preorderDate = PreorderDate::factory()->create();

        $this->expectException(HttpException::class);
        (new PriceCartAction)->execute(
            items: [['product_variant_id' => $variant->id, 'quantity' => 1]],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'pickup',
        );
    }

    public function test_rejects_inactive_product(): void
    {
        $product = Product::factory()->create(['is_active' => false]);
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();

        $this->expectException(HttpException::class);
        (new PriceCartAction)->execute(
            items: [['product_variant_id' => $variant->id, 'quantity' => 1]],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'pickup',
        );
    }

    public function test_applies_delivery_zone_fee_for_delivery_fulfilment(): void
    {
        $product = Product::factory()->create(['base_price_sen' => 3000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 0]);
        $preorderDate = PreorderDate::factory()->create();
        $zone = DeliveryZone::factory()->create(['delivery_fee_sen' => 900]);
        DeliveryZonePostcode::factory()->for($zone, 'deliveryZone')->create(['postcode' => '43000']);

        $quote = (new PriceCartAction)->execute(
            items: [['product_variant_id' => $variant->id, 'quantity' => 1]],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'delivery',
            deliveryPostcode: '43000',
        );

        $this->assertSame(900, $quote->deliveryFeeSen);
        $this->assertSame(3900, $quote->totalSen);
    }
}
