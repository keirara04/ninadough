<?php

namespace App\Console\Commands;

use App\Actions\Orders\TransitionOrderStatusAction;
use App\Models\Order;
use Illuminate\Console\Command;

class ExpireStaleOrders extends Command
{
    protected $signature = 'orders:expire-stale';

    protected $description = 'Expire awaiting_payment orders past their deadline, releasing reserved stock and capacity. Never touches payment_submitted orders.';

    public function handle(TransitionOrderStatusAction $transitionAction): int
    {
        $staleOrderIds = Order::query()
            ->where('status', 'awaiting_payment')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->pluck('id');

        foreach ($staleOrderIds as $orderId) {
            $transitionAction->execute(
                orderId: $orderId,
                toStatus: 'expired',
                actorType: 'system',
            );
        }

        $this->info("Expired {$staleOrderIds->count()} stale order(s).");

        return self::SUCCESS;
    }
}
