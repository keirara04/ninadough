<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductOptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOptionGroupFactory extends Factory
{
    protected $model = ProductOptionGroup::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->unique()->randomElement(['Size', 'Flavour', 'Topping']),
            'selection_type' => 'single',
            'is_required' => true,
            'sort_order' => 0,
        ];
    }
}
