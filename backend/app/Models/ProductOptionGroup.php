<?php

namespace App\Models;

use Database\Factories\ProductOptionGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'name', 'selection_type', 'is_required', 'sort_order'])]
class ProductOptionGroup extends Model
{
    /** @use HasFactory<ProductOptionGroupFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
