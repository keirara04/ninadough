<?php

namespace Tests\Feature\Catalogue;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_categories_ordered_by_sort_order(): void
    {
        ProductCategory::factory()->create(['name' => 'Drinks', 'sort_order' => 1]);
        ProductCategory::factory()->create(['name' => 'Cakes', 'sort_order' => 0]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.name', 'Cakes');
        $response->assertJsonPath('data.1.name', 'Drinks');
    }
}
