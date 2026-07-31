<?php

namespace Tests\Feature\Schema;

use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOptionsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_two_option_groups_with_same_name_on_same_product(): void
    {
        $product = Product::factory()->create();
        ProductOptionGroup::factory()->for($product)->create(['name' => 'Size']);

        $this->expectException(QueryException::class);
        ProductOptionGroup::factory()->for($product)->create(['name' => 'Size']);
    }

    public function test_rejects_two_option_values_with_same_value_code_in_same_group(): void
    {
        $group = ProductOptionGroup::factory()->create();
        ProductOptionValue::factory()->for($group, 'productOptionGroup')->create(['value_code' => 'small']);

        $this->expectException(QueryException::class);
        ProductOptionValue::factory()->for($group, 'productOptionGroup')->create(['value_code' => 'small']);
    }
}
