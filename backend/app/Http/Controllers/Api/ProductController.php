<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['images', 'optionGroups.values', 'variants.optionValues'])
            ->orderBy('sort_order')
            ->get();

        return ProductResource::collection($products);
    }
}
