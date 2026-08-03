<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Cakes', 'slug' => 'cakes', 'sort_order' => 0],
            ['name' => 'Cookies', 'slug' => 'cookies', 'sort_order' => 1],
            ['name' => 'Pastries', 'slug' => 'pastries', 'sort_order' => 2],
            ['name' => 'Drinks', 'slug' => 'drinks', 'sort_order' => 3],
        ])->map(fn (array $attributes) => ProductCategory::firstOrCreate(
            ['slug' => $attributes['slug']],
            $attributes,
        ));

        $products = Product::orderBy('sort_order')->orderBy('id')->get();

        $products->each(function (Product $product, int $index) use ($categories) {
            if ($product->category_id === null) {
                $product->update(['category_id' => $categories[$index % $categories->count()]->id]);
            }
        });

        $products->take(4)->each(fn (Product $product) => $product->update(['is_featured' => true]));
    }
}
