<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone_e164', 'email', 'marketing_consent_at', 'last_order_at'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'marketing_consent_at' => 'datetime',
            'last_order_at' => 'datetime',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
