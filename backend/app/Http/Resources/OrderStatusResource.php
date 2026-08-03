<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'total_sen' => $this->total_sen,
            'awaiting_proof' => $this->payment_status === 'awaiting_payment',
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
