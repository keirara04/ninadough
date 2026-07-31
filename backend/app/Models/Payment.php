<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_id', 'method', 'provider', 'provider_reference', 'amount_sen', 'currency',
    'status', 'paid_at', 'confirmed_by_user_id', 'gateway_payload',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUlid;

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }
}
