<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'preorder_date_id' => ['required', 'integer', 'exists:preorder_dates,id'],
            'fulfilment_method' => ['required', 'string', 'in:pickup,delivery'],
            'delivery_zone_id' => ['required_if:fulfilment_method,delivery', 'nullable', 'integer', 'exists:delivery_zones,id'],
        ];
    }
}
