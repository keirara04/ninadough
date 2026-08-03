<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCategoryController extends Controller
{
    private const MAX_PER_PAGE = 50;

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', self::MAX_PER_PAGE), 1), self::MAX_PER_PAGE);

        return ProductCategoryResource::collection(
            ProductCategory::query()->orderBy('sort_order')->paginate($perPage)->withQueryString(),
        );
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'unique:product_categories,slug'],
            'sort_order' => ['nullable', 'integer'],
        ])->validate();

        $category = ProductCategory::create($validated);

        return new ProductCategoryResource($category);
    }

    public function update(Request $request, ProductCategory $category)
    {
        $validated = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => ['sometimes', 'string', 'max:140', 'unique:product_categories,slug,'.$category->id],
            'sort_order' => ['sometimes', 'integer'],
        ])->validate();

        $category->update($validated);

        return new ProductCategoryResource($category);
    }

    public function destroy(ProductCategory $category)
    {
        $category->delete();

        return response()->json(['data' => ['ok' => true]]);
    }
}
