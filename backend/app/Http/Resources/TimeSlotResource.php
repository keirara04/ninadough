<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'starts_at' => substr((string) $this->starts_at, 0, 5),
            'ends_at' => substr((string) $this->ends_at, 0, 5),
            'fulfilment_method' => $this->fulfilment_method,
            'capacity_limit' => $this->capacity_limit,
            'remaining_capacity' => max(0, $this->capacity_limit - $this->reserved_capacity),
            'is_active' => $this->is_active,
        ];
    }
}
