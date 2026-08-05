<?php

namespace App\Http\Requests\Checkout;

use App\Rules\PreorderDateExists;
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
            'preorder_date' => ['required', 'date_format:Y-m-d', new PreorderDateExists],
            'fulfilment_method' => ['required', 'string', 'in:pickup,delivery'],
            'postcode' => ['required_if:fulfilment_method,delivery', 'nullable', 'string', 'max:20'],
        ];
    }
}
