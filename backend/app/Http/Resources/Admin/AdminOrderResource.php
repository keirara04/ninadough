<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'checkout_channel' => $this->checkout_channel,
            'fulfilment_method' => $this->fulfilment_method,
            'customer_name' => $this->customer_name_snapshot,
            'customer_phone' => $this->customer_phone_snapshot,
            'total_sen' => $this->total_sen,
            'rejection_message' => $this->rejection_message,
            'refund_required' => $this->refund_required,
            'refund_note' => $this->refund_note,
            'notes' => $this->notes,
            'card_message' => $this->card_message,
            'allergies_note' => $this->allergies_note,
            'hide_price_on_package' => $this->hide_price_on_package,
            'time_slot' => $this->whenLoaded('timeSlot', fn () => $this->timeSlot ? [
                'label' => $this->timeSlot->label,
                'starts_at' => substr((string) $this->timeSlot->starts_at, 0, 5),
                'ends_at' => substr((string) $this->timeSlot->ends_at, 0, 5),
            ] : null),
            'created_at' => $this->created_at->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_name' => $item->product_name_snapshot,
                'variant_name' => $item->variant_name_snapshot,
                'quantity' => $item->quantity,
                'line_total_sen' => $item->line_total_sen,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount_sen' => $payment->amount_sen,
                'proofs' => $payment->proofs->map(fn ($proof) => [
                    'id' => $proof->id,
                    'url' => route('admin.payment-proofs.show', ['proof' => $proof->id]),
                    'uploaded_at' => $proof->uploaded_at->toIso8601String(),
                    'superseded_at' => $proof->superseded_at?->toIso8601String(),
                    'reviewed_at' => $proof->reviewed_at?->toIso8601String(),
                ]),
            ])),
        ];
    }
}
