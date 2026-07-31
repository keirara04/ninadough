<?php

namespace App\Models;

use Database\Factories\DeliveryZonePostcodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['delivery_zone_id', 'postcode'])]
class DeliveryZonePostcode extends Model
{
    /** @use HasFactory<DeliveryZonePostcodeFactory> */
    use HasFactory;

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }
}
