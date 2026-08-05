<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'ulid', 'order_number', 'customer_id', 'preorder_date_id', 'time_slot_id', 'checkout_channel',
    'fulfilment_method', 'delivery_zone_id', 'customer_name_snapshot', 'customer_phone_snapshot',
    'customer_email_snapshot', 'delivery_address', 'pickup_instruction_snapshot', 'subtotal_sen',
    'delivery_fee_sen', 'discount_sen', 'total_sen', 'total_capacity_units', 'status',
    'payment_status', 'payment_method', 'expires_at', 'capacity_released_at', 'slot_capacity_released_at',
    'confirmed_at', 'paid_at', 'completed_at', 'idempotency_key', 'source_metadata',
    'rejection_message', 'refund_required', 'refund_note',
    'notes', 'card_message', 'allergies_note', 'hide_price_on_package',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUlid;

    protected function casts(): array
    {
        return [
            'delivery_address' => 'array',
            'source_metadata' => 'array',
            'expires_at' => 'datetime',
            'capacity_released_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'slot_capacity_released_at' => 'datetime',
            'refund_required' => 'boolean',
            'hide_price_on_package' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function preorderDate()
    {
        return $this->belongsTo(PreorderDate::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusEvents()
    {
        return $this->hasMany(OrderStatusEvent::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
