<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->words(2, true),
            'price_adjustment_sen' => 0,
            'capacity_units' => 1,
            'stock_quantity' => 100,
            'reserved_quantity' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
