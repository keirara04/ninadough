<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'channel' => 'whatsapp_link',
            'template_key' => 'order_confirmed',
            'recipient' => '+60123456789',
            'status' => 'queued',
        ];
    }
}
