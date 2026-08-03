<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'delivery_fee_sen' => $this->delivery_fee_sen,
            'minimum_order_sen' => $this->minimum_order_sen,
        ];
    }
}
