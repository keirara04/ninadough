<?php

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\PriceCartAction;
use App\Exceptions\LeadTimeNotMetException;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTimeEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_preorder_date_earlier_than_product_lead_time(): void
    {
        $product = Product::factory()->create(['min_lead_time_days' => 5]);
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create([
            'order_date' => now()->addDays(2)->toDateString(),
            'cutoff_at' => now()->addDay(),
        ]);

        $this->expectException(LeadTimeNotMetException::class);
        (new PriceCartAction)->execute(
            items: [['product_variant_id' => $variant->id, 'quantity' => 1]],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'pickup',
        );
    }

    public function test_accepts_preorder_date_meeting_product_lead_time(): void
    {
        $product = Product::factory()->create(['min_lead_time_days' => 2]);
        $variant = ProductVariant::factory()->for($product)->create();
        $preorderDate = PreorderDate::factory()->create([
            'order_date' => now()->addDays(3)->toDateString(),
            'cutoff_at' => now()->addDays(2),
        ]);

        $quote = (new PriceCartAction)->execute(
            items: [['product_variant_id' => $variant->id, 'quantity' => 1]],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'pickup',
        );

        $this->assertSame($preorderDate->id, $quote->preorderDateId);
    }

    public function test_uses_longest_lead_time_across_cart_items(): void
    {
        $shortLead = Product::factory()->create(['min_lead_time_days' => 1]);
        $shortVariant = ProductVariant::factory()->for($shortLead)->create();
        $longLead = Product::factory()->create(['min_lead_time_days' => 4]);
        $longVariant = ProductVariant::factory()->for($longLead)->create();
        $preorderDate = PreorderDate::factory()->create([
            'order_date' => now()->addDays(2)->toDateString(),
            'cutoff_at' => now()->addDay(),
        ]);

        $this->expectException(LeadTimeNotMetException::class);
        (new PriceCartAction)->execute(
            items: [
                ['product_variant_id' => $shortVariant->id, 'quantity' => 1],
                ['product_variant_id' => $longVariant->id, 'quantity' => 1],
            ],
            orderDate: $preorderDate->order_date->toDateString(),
            fulfilmentMethod: 'pickup',
        );
    }
}
