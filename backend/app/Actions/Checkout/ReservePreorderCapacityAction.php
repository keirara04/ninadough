<?php

namespace App\Actions\Checkout;

use App\Exceptions\PreorderDateFullException;
use App\Exceptions\PreorderDateUnavailableException;
use App\Models\PreorderDate;

class ReservePreorderCapacityAction
{
    /**
     * Must be called from within an outer DB::transaction() that also
     * persists the order — the lock, the capacity increment, and the
     * order insert are one atomic unit.
     */
    public function execute(int $preorderDateId, int $units, string $fulfilmentMethod): PreorderDate
    {
        $date = PreorderDate::whereKey($preorderDateId)->lockForUpdate()->first();

        if (! $date || $date->status === 'closed') {
            throw new PreorderDateUnavailableException;
        }

        if ($date->status === 'full' || $date->reserved_capacity + $units > $date->capacity_limit) {
            throw new PreorderDateFullException;
        }

        if (now()->greaterThanOrEqualTo($date->cutoff_at)) {
            throw new PreorderDateUnavailableException;
        }

        if ($fulfilmentMethod === 'delivery' && ! $date->delivery_enabled) {
            throw new PreorderDateUnavailableException;
        }

        if ($fulfilmentMethod === 'pickup' && ! $date->pickup_enabled) {
            throw new PreorderDateUnavailableException;
        }

        $date->reserved_capacity += $units;
        if ($date->reserved_capacity >= $date->capacity_limit) {
            $date->status = 'full';
        }
        $date->save();

        return $date;
    }
}
