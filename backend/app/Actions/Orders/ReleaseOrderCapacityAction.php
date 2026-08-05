<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderStatusEvent;
use App\Models\PreorderDate;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class ReleaseOrderCapacityAction
{
    /**
     * Shared by cancel/reject flows and the future WhatsApp-expiry scheduler.
     * Releases reserved capacity exactly once per order, guarded by
     * capacity_released_at, then transitions the order to $toStatus.
     */
    public function execute(
        int $orderId,
        string $toStatus,
        string $actorType = 'system',
        ?int $actorUserId = null,
        ?string $noteInternal = null,
    ): Order {
        return DB::transaction(function () use ($orderId, $toStatus, $actorType, $actorUserId, $noteInternal) {
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            $fromStatus = $order->status;

            $this->releaseCapacityOnly($order);

            $order->status = $toStatus;
            $order->save();

            OrderStatusEvent::create([
                'order_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_type' => $actorType,
                'actor_user_id' => $actorUserId,
                'note_internal' => $noteInternal,
            ]);

            return $order;
        });
    }

    /**
     * Releases reserved preorder-date capacity for $order, guarded by
     * capacity_released_at so it only ever fires once. Does not save
     * $order or touch its status — caller is responsible for both.
     * $order must already be locked by the caller.
     */
    public function releaseCapacityOnly(Order $order): void
    {
        if ($order->capacity_released_at !== null) {
            return;
        }

        $date = PreorderDate::whereKey($order->preorder_date_id)->lockForUpdate()->first();

        if ($date) {
            $date->reserved_capacity = max(0, $date->reserved_capacity - $order->total_capacity_units);
            if ($date->status === 'full' && $date->reserved_capacity < $date->capacity_limit) {
                $date->status = 'open';
            }
            $date->save();
        }

        $order->capacity_released_at = now();

        $this->releaseTimeSlotOnly($order);
    }

    /**
     * Releases reserved time-slot capacity for $order, guarded by
     * slot_capacity_released_at so it only ever fires once. No-op if the
     * order has no time slot. Does not save $order — caller is responsible.
     * $order must already be locked by the caller.
     */
    private function releaseTimeSlotOnly(Order $order): void
    {
        if ($order->slot_capacity_released_at !== null || $order->time_slot_id === null) {
            return;
        }

        $slot = TimeSlot::whereKey($order->time_slot_id)->lockForUpdate()->first();

        if ($slot) {
            $slot->reserved_capacity = max(0, $slot->reserved_capacity - 1);
            $slot->save();
        }

        $order->slot_capacity_released_at = now();
    }
}
