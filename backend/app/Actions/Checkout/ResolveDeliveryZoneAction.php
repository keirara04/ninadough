<?php

namespace App\Actions\Checkout;

use App\Exceptions\PostcodeNotServedException;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePostcode;

class ResolveDeliveryZoneAction
{
    /**
     * Single source of truth for postcode -> zone resolution. The client
     * never supplies delivery_zone_id directly — it is always derived
     * server-side from the submitted postcode.
     */
    public function execute(string $postcode): DeliveryZone
    {
        $match = DeliveryZonePostcode::with('deliveryZone')->where('postcode', $postcode)->first();

        if (! $match || ! $match->deliveryZone || ! $match->deliveryZone->is_active) {
            throw new PostcodeNotServedException;
        }

        return $match->deliveryZone;
    }
}
