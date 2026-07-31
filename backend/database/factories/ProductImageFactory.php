<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'storage_disk' => 'spaces',
            'object_key' => 'products/'.$this->faker->uuid().'.jpg',
            'sort_order' => 0,
            'is_primary' => false,
        ];
    }
}
