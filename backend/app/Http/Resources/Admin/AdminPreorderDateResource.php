<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPreorderDateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_date' => $this->order_date->toDateString(),
            'cutoff_at' => $this->cutoff_at->toIso8601String(),
            'capacity_limit' => $this->capacity_limit,
            'reserved_capacity' => $this->reserved_capacity,
            'pickup_enabled' => $this->pickup_enabled,
            'delivery_enabled' => $this->delivery_enabled,
            'status' => $this->status,
            'note_internal' => $this->note_internal,
        ];
    }
}
