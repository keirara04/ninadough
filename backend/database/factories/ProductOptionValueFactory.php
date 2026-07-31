<?php

namespace Database\Factories;

use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOptionValueFactory extends Factory
{
    protected $model = ProductOptionValue::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'product_option_group_id' => ProductOptionGroup::factory(),
            'name' => ucfirst($name),
            'value_code' => str($name)->slug(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
