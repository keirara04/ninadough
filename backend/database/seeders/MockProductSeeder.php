<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class MockProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Red Velvet Cake', 'category' => 'cakes', 'price' => 9500, 'featured' => true, 'image' => 'cake-red-velvet', 'lead_time' => 2],
            ['name' => 'Classic Cheesecake', 'category' => 'cakes', 'price' => 8800, 'featured' => false, 'image' => 'cake-cheesecake', 'lead_time' => 2, 'sold_out' => true],
            ['name' => 'Matcha Swiss Roll', 'category' => 'cakes', 'price' => 7200, 'featured' => true, 'image' => 'cake-matcha-roll', 'lead_time' => 1],
            ['name' => 'Chocolate Chip Cookies', 'category' => 'cookies', 'price' => 2500, 'featured' => false, 'image' => 'cookie-choc-chip', 'lead_time' => 0],
            ['name' => 'Oatmeal Raisin Cookies', 'category' => 'cookies', 'price' => 2400, 'featured' => false, 'image' => 'cookie-oatmeal', 'lead_time' => 0],
            ['name' => 'Macarons Box of 6', 'category' => 'cookies', 'price' => 3600, 'featured' => true, 'image' => 'cookie-macarons', 'lead_time' => 1],
            ['name' => 'Croissant', 'category' => 'pastries', 'price' => 1200, 'featured' => false, 'image' => 'pastry-croissant', 'lead_time' => 0],
            ['name' => 'Pain au Chocolat', 'category' => 'pastries', 'price' => 1400, 'featured' => false, 'image' => 'pastry-pain-chocolat', 'lead_time' => 0],
            ['name' => 'Cinnamon Roll', 'category' => 'pastries', 'price' => 1600, 'featured' => true, 'image' => 'pastry-cinnamon-roll', 'lead_time' => 0],
            ['name' => 'Iced Latte', 'category' => 'drinks', 'price' => 1800, 'featured' => false, 'image' => 'drink-iced-latte', 'lead_time' => 0],
            ['name' => 'Fresh Orange Juice', 'category' => 'drinks', 'price' => 1500, 'featured' => false, 'image' => 'drink-orange-juice', 'lead_time' => 0],
        ];

        foreach ($items as $index => $item) {
            $category = ProductCategory::where('slug', $item['category'])->first();

            $product = Product::factory()->create([
                'name' => $item['name'],
                'slug' => str($item['name'])->slug(),
                'base_price_sen' => $item['price'],
                'is_featured' => $item['featured'],
                'category_id' => $category?->id,
                'sort_order' => $index + 1,
                'min_lead_time_days' => $item['lead_time'],
            ]);

            ProductImage::factory()->for($product)->create([
                'is_primary' => true,
                'public_url' => "https://picsum.photos/seed/{$item['image']}/800/800",
                'alt_text' => $item['name'],
            ]);

            $sizeGroup = $product->optionGroups()->create([
                'name' => 'Size', 'selection_type' => 'single', 'is_required' => true, 'sort_order' => 0,
            ]);
            $small = ProductOptionValue::factory()->for($sizeGroup, 'productOptionGroup')->create([
                'name' => 'Regular', 'value_code' => 'regular',
            ]);
            $large = ProductOptionValue::factory()->for($sizeGroup, 'productOptionGroup')->create([
                'name' => 'Large', 'value_code' => 'large',
            ]);

            $soldOut = $item['sold_out'] ?? false;

            $regularVariant = ProductVariant::factory()->for($product)->create([
                'name' => $item['name'].' - Regular', 'price_adjustment_sen' => 0, 'capacity_units' => 1,
                'stock_quantity' => $soldOut ? 0 : 100,
            ]);
            $regularVariant->optionValues()->attach($small);

            $largeVariant = ProductVariant::factory()->for($product)->create([
                'name' => $item['name'].' - Large', 'price_adjustment_sen' => (int) round($item['price'] * 0.4), 'capacity_units' => 2,
                'stock_quantity' => $soldOut ? 0 : 100,
            ]);
            $largeVariant->optionValues()->attach($large);
        }
    }
}
