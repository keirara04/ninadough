<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminProductImageController extends Controller
{
    private const DISK = 'spaces';

    private const MAX_IMAGES_PER_PRODUCT = 6;

    public function store(Request $request, Product $product)
    {
        $validated = Validator::make($request->all(), [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ])->validate();

        abort_if($product->images()->count() >= self::MAX_IMAGES_PER_PRODUCT, 422, 'This product already has the maximum number of images.');

        $file = $request->file('image');
        $path = $file->store('products', self::DISK);

        $image = DB::transaction(function () use ($product, $path, $validated) {
            $isFirst = $product->images()->count() === 0;

            return ProductImage::create([
                'product_id' => $product->id,
                'storage_disk' => self::DISK,
                'object_key' => $path,
                'public_url' => Storage::disk(self::DISK)->url($path),
                'alt_text' => $validated['alt_text'] ?? null,
                'sort_order' => $product->images()->count(),
                'is_primary' => $isFirst,
            ]);
        });

        return new ProductImageResource($image);
    }

    public function destroy(Product $product, ProductImage $image)
    {
        abort_if($image->product_id !== $product->id, 404);

        Storage::disk($image->storage_disk)->delete($image->object_key);
        $image->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function primary(Product $product, ProductImage $image)
    {
        abort_if($image->product_id !== $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            $product->images()->where('id', '!=', $image->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return new ProductImageResource($image->fresh());
    }
}
