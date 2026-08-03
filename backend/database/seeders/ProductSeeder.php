<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::factory()->create([
            'name' => 'Chocolate Fudge Cake',
            'slug' => 'chocolate-fudge-cake',
            'base_price_sen' => 8000,
        ]);

        ProductImage::factory()->for($product)->create([
            'is_primary' => true,
            'public_url' => 'https://picsum.photos/seed/cake-chocolate-fudge/800/800',
            'alt_text' => $product->name,
        ]);

        $sizeGroup = $product->optionGroups()->create([
            'name' => 'Size', 'selection_type' => 'single', 'is_required' => true, 'sort_order' => 0,
        ]);
        $small = ProductOptionValue::factory()->for($sizeGroup, 'productOptionGroup')->create([
            'name' => 'Small (6")', 'value_code' => 'small',
        ]);
        $large = ProductOptionValue::factory()->for($sizeGroup, 'productOptionGroup')->create([
            'name' => 'Large (9")', 'value_code' => 'large',
        ]);

        $smallVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Chocolate Fudge Cake - Small', 'price_adjustment_sen' => 0, 'capacity_units' => 1,
        ]);
        $smallVariant->optionValues()->attach($small);

        $largeVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Chocolate Fudge Cake - Large', 'price_adjustment_sen' => 3000, 'capacity_units' => 2,
        ]);
        $largeVariant->optionValues()->attach($large);
    }
}
