<?php

namespace Tests\Feature\Schema;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_product_with_ulid_and_soft_deletes(): void
    {
        $product = Product::factory()->create();

        $this->assertSame(26, strlen($product->ulid));

        $product->delete();

        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }

    public function test_rejects_negative_base_price_sen(): void
    {
        $this->expectException(QueryException::class);
        Product::factory()->create(['base_price_sen' => -100]);
    }

    public function test_only_allows_one_primary_image_per_product(): void
    {
        $product = Product::factory()->create();
        ProductImage::factory()->for($product)->create(['is_primary' => true]);

        $this->expectException(QueryException::class);
        ProductImage::factory()->for($product)->create(['is_primary' => true]);
    }

    public function test_deletes_product_images_when_product_is_force_deleted(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->for($product)->create();

        $product->forceDelete();

        $this->assertNull(ProductImage::find($image->id));
    }
}
