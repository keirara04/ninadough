<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    private const MAX_PER_PAGE = 5;

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->integer('per_page', self::MAX_PER_PAGE), self::MAX_PER_PAGE);

        $products = Product::query()
            ->where('is_active', true)
            ->when($request->string('q')->trim()->isNotEmpty(), fn ($query) => $query->where(
                'name', 'ilike', '%'.$request->string('q')->trim().'%',
            ))
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category', fn ($categoryQuery) => $categoryQuery->where('slug', $request->string('category')),
            ))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->with(['category', 'images', 'optionGroups.values', 'variants.optionValues'])
            ->orderBy('sort_order')
            ->paginate(max($perPage, 1))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->with(['category', 'images', 'optionGroups.values', 'variants.optionValues'])
            ->firstOrFail();

        return new ProductResource($product);
    }
}
