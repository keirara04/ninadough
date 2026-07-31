<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentProofFactory extends Factory
{
    protected $model = PaymentProof::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'storage_disk' => 'spaces',
            'object_key' => 'payment-proofs/'.$this->faker->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 204800,
            'uploaded_at' => now(),
        ];
    }
}
