<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PreorderDate;
use Illuminate\Database\Seeder;

class OrderDemoSeeder extends Seeder
{
    public function run(): void
    {
        $preorderDate = PreorderDate::first() ?? PreorderDate::factory()->create();

        $statuses = [
            'whatsapp_pending', 'awaiting_payment', 'payment_submitted', 'payment_confirmed',
            'preparing', 'ready_for_pickup', 'out_for_delivery', 'completed', 'cancelled',
            'rejected', 'expired',
        ];

        foreach ($statuses as $status) {
            $order = Order::factory()->create([
                'preorder_date_id' => $preorderDate->id,
                'status' => $status,
                'checkout_channel' => in_array($status, ['whatsapp_pending', 'expired'], true) ? 'whatsapp' : 'website',
                'payment_status' => $status === 'completed' ? 'paid' : 'awaiting_payment',
                'expires_at' => in_array($status, ['whatsapp_pending', 'expired'], true) ? now()->addMinutes(20) : null,
            ]);

            OrderItem::factory()->for($order)->create();
        }
    }
}
