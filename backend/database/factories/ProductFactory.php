<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'base_price_sen' => $this->faker->numberBetween(500, 15000),
            'default_capacity_units' => 1,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
