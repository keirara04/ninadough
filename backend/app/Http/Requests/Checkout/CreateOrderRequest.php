<?php

namespace App\Http\Requests\Checkout;

use App\Rules\PreorderDateExists;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
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
            'time_slot_id' => ['nullable', 'integer', 'exists:time_slots,id'],
            'checkout_channel' => ['required', 'string', 'in:website,whatsapp'],
            'fulfilment_method' => ['required', 'string', 'in:pickup,delivery'],
            'payment_method' => ['nullable', 'string', 'in:bank_transfer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'card_message' => ['nullable', 'string', 'max:200'],
            'allergies_note' => ['nullable', 'string', 'max:500'],
            'hide_price_on_package' => ['nullable', 'boolean'],
            'delivery_address' => ['required_if:fulfilment_method,delivery', 'nullable', 'array'],
            'delivery_address.recipient_name' => ['required_if:fulfilment_method,delivery', 'string', 'max:160'],
            'delivery_address.recipient_phone_e164' => ['required_if:fulfilment_method,delivery', 'string', 'max:20'],
            'delivery_address.line_1' => ['required_if:fulfilment_method,delivery', 'string', 'max:255'],
            'delivery_address.line_2' => ['nullable', 'string', 'max:255'],
            'delivery_address.city' => ['required_if:fulfilment_method,delivery', 'string', 'max:120'],
            'delivery_address.state' => ['required_if:fulfilment_method,delivery', 'string', 'max:120'],
            'delivery_address.postcode' => ['required_if:fulfilment_method,delivery', 'string', 'max:20'],
            'idempotency_key' => ['required', 'uuid'],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:160'],
            'customer.phone_e164' => ['required', 'string', 'max:20', 'regex:/^\+[1-9]\d{6,14}$/'],
            'customer.email' => [
                $this->input('checkout_channel') === 'website' ? 'required' : 'nullable',
                'email', 'max:255',
            ],
        ];
    }
}
