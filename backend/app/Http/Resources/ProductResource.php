<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'base_price_sen' => $this->base_price_sen,
            'min_lead_time_days' => $this->min_lead_time_days,
            'allergen_information' => $this->allergen_information,
            'is_featured' => $this->is_featured,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'images' => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->public_url,
                'alt_text' => $image->alt_text,
                'is_primary' => $image->is_primary,
            ]),
            'option_groups' => $this->optionGroups->map(fn ($group) => [
                'name' => $group->name,
                'selection_type' => $group->selection_type,
                'is_required' => $group->is_required,
                'values' => $group->values->where('is_active', true)->values()->map(fn ($value) => [
                    'name' => $value->name,
                    'value_code' => $value->value_code,
                ]),
            ]),
            'variants' => $this->variants->where('is_active', true)->values()->map(fn ($variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'price_sen' => $this->base_price_sen + $variant->price_adjustment_sen,
                'capacity_units' => $variant->capacity_units ?? $this->default_capacity_units,
                'is_available' => ($variant->stock_quantity - $variant->reserved_quantity) > 0,
                'option_values' => $variant->optionValues->map(fn ($value) => $value->name),
            ]),
        ];
    }
}
