<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreorderDateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_date' => $this->order_date->toDateString(),
            'status' => $this->status,
            'cutoff_at' => $this->cutoff_at->toIso8601String(),
            'remaining_capacity' => max(0, $this->capacity_limit - $this->reserved_capacity),
            'pickup_enabled' => $this->pickup_enabled,
            'delivery_enabled' => $this->delivery_enabled,
        ];
    }
}
