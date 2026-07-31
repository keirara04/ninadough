<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => 'bank_transfer',
            'amount_sen' => 3600,
            'currency' => 'MYR',
            'status' => 'pending',
        ];
    }
}
