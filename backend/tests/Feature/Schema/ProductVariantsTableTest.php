<?php

namespace Tests\Feature\Schema;

use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_capacity_units_that_is_not_positive(): void
    {
        $product = Product::factory()->create();

        $this->expectException(QueryException::class);
        ProductVariant::factory()->for($product)->create(['capacity_units' => 0]);
    }

    public function test_attaches_option_values_to_variant_through_pivot(): void
    {
        $variant = ProductVariant::factory()->create();
        $value = ProductOptionValue::factory()->create();

        $variant->optionValues()->attach($value);

        $this->assertCount(1, $variant->fresh()->optionValues);
    }

    public function test_rejects_attaching_same_option_value_twice(): void
    {
        $variant = ProductVariant::factory()->create();
        $value = ProductOptionValue::factory()->create();
        $variant->optionValues()->attach($value);

        $this->expectException(QueryException::class);
        $variant->optionValues()->attach($value);
    }
}
