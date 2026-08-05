<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->hasValidSignature();
    }

    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:jpeg,png,webp,pdf', 'max:5120'],
        ];
    }
}
