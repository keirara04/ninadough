<?php

namespace App\Actions\Checkout;

use App\Exceptions\TimeSlotFullException;
use App\Exceptions\TimeSlotUnavailableException;
use App\Models\TimeSlot;

class ReserveTimeSlotCapacityAction
{
    /**
     * Must be called from within an outer DB::transaction() that also
     * persists the order — mirrors ReservePreorderCapacityAction. Capacity
     * here is a per-slot order count (not product capacity units).
     */
    public function execute(int $timeSlotId, int $preorderDateId, string $fulfilmentMethod): TimeSlot
    {
        $slot = TimeSlot::whereKey($timeSlotId)->lockForUpdate()->first();

        if (! $slot || ! $slot->is_active || $slot->preorder_date_id !== $preorderDateId || ! $slot->appliesTo($fulfilmentMethod)) {
            throw new TimeSlotUnavailableException;
        }

        if ($slot->reserved_capacity + 1 > $slot->capacity_limit) {
            throw new TimeSlotFullException;
        }

        $slot->reserved_capacity += 1;
        $slot->save();

        return $slot;
    }
}
