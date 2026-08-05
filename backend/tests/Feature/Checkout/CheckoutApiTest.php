<?php

namespace Tests\Feature\Checkout;

use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_products_with_variants_and_images(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Cakes']);
        $active = Product::factory()->create([
            'is_active' => true,
            'name' => 'Active Cake',
            'is_featured' => true,
            'category_id' => $category->id,
        ]);
        ProductImage::factory()->for($active)->create(['is_primary' => true, 'public_url' => 'https://cdn.test/a.jpg']);
        ProductVariant::factory()->for($active)->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false, 'name' => 'Hidden Cake']);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Active Cake');
        $response->assertJsonPath('data.0.images.0.url', 'https://cdn.test/a.jpg');
        $response->assertJsonPath('data.0.is_featured', true);
        $response->assertJsonPath('data.0.category.name', 'Cakes');
    }

    public function test_lists_upcoming_preorder_dates_with_remaining_capacity(): void
    {
        PreorderDate::factory()->create([
            'order_date' => now()->addDays(2)->toDateString(),
            'capacity_limit' => 10,
            'reserved_capacity' => 4,
        ]);

        $response = $this->getJson('/api/v1/preorder-dates');

        $response->assertOk();
        $response->assertJsonPath('data.0.remaining_capacity', 6);
    }

    public function test_checkout_quote_returns_server_computed_total_ignoring_client_price(): void
    {
        $product = Product::factory()->create(['base_price_sen' => 4000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 0]);
        $preorderDate = PreorderDate::factory()->create();

        $response = $this->postJson('/api/v1/checkout/quote', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 2, 'unit_price_sen' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'fulfilment_method' => 'pickup',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.subtotal_sen', 8000);
        $response->assertJsonPath('data.total_sen', 8000);
    }

    public function test_checkout_quote_requires_postcode_when_fulfilment_is_delivery(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();

        $response = $this->postJson('/api/v1/checkout/quote', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'fulfilment_method' => 'delivery',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('postcode');
    }

    public function test_checkout_quote_rejects_client_supplied_delivery_zone_id(): void
    {
        $product = Product::factory()->create(['base_price_sen' => 5000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 0]);
        $preorderDate = PreorderDate::factory()->create();
        $zone = DeliveryZone::factory()->create(['delivery_fee_sen' => 999]);

        // Client sends a zone_id with no matching postcode — server must ignore it and reject.
        $response = $this->postJson('/api/v1/checkout/quote', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'fulfilment_method' => 'delivery',
            'delivery_zone_id' => $zone->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('postcode');
    }

    public function test_checkout_quote_derives_delivery_zone_fee_from_postcode(): void
    {
        $product = Product::factory()->create(['base_price_sen' => 5000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_adjustment_sen' => 0]);
        $preorderDate = PreorderDate::factory()->create();
        $zone = DeliveryZone::factory()->create(['delivery_fee_sen' => 700]);
        DeliveryZonePostcode::factory()->for($zone, 'deliveryZone')->create(['postcode' => '50000']);

        $response = $this->postJson('/api/v1/checkout/quote', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'fulfilment_method' => 'delivery',
            'postcode' => '50000',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.total_sen', 5700);
    }

    public function test_checkout_quote_rejects_unserved_postcode(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create();

        $response = $this->postJson('/api/v1/checkout/quote', [
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            'preorder_date' => $preorderDate->order_date->toDateString(),
            'fulfilment_method' => 'delivery',
            'postcode' => '99999',
        ]);

        $response->assertStatus(422);
    }
}
