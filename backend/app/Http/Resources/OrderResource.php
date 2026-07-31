<?php

namespace App\Http\Resources;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'checkout_channel' => $this->checkout_channel,
            'fulfilment_method' => $this->fulfilment_method,
            'subtotal_sen' => $this->subtotal_sen,
            'delivery_fee_sen' => $this->delivery_fee_sen,
            'total_sen' => $this->total_sen,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'whatsapp_url' => $this->checkout_channel === 'whatsapp' ? $this->buildWhatsappUrl() : null,
        ];
    }

    private function buildWhatsappUrl(): ?string
    {
        $number = BusinessSetting::where('key', 'whatsapp_number')->first()?->value;
        if (! $number) {
            return null;
        }

        $message = sprintf(
            'Order %s, total RM%s, %s. Please confirm my preorder.',
            $this->order_number,
            number_format($this->total_sen / 100, 2),
            $this->fulfilment_method,
        );

        return 'https://wa.me/'.ltrim((string) $number, '+').'?text='.rawurlencode($message);
    }
}
