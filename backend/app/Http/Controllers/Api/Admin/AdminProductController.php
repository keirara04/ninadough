<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    private const MAX_PER_PAGE = 5;

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', self::MAX_PER_PAGE), 1), self::MAX_PER_PAGE);

        $products = Product::query()
            ->with(['category', 'images', 'optionGroups.values', 'variants.optionValues'])
            ->orderBy('sort_order')
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $product = Product::create($validated + [
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);

        return new ProductResource($product);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validated($request, $product->id);

        $product->update($validated);

        return new ProductResource($product->fresh(['category', 'images', 'optionGroups.values', 'variants.optionValues']));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    private function validated(Request $request, ?int $productId = null): array
    {
        return Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:products,slug,'.$productId],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'base_price_sen' => ['required', 'integer', 'min:0'],
            'min_lead_time_days' => ['nullable', 'integer', 'min:0'],
            'allergen_information' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ])->validate();
    }
}
