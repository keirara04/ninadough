<?php

namespace App\Models;

use Database\Factories\TimeSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'preorder_date_id', 'label', 'starts_at', 'ends_at', 'fulfilment_method',
    'capacity_limit', 'reserved_capacity', 'is_active',
])]
class TimeSlot extends Model
{
    /** @use HasFactory<TimeSlotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function preorderDate()
    {
        return $this->belongsTo(PreorderDate::class);
    }

    public function appliesTo(string $fulfilmentMethod): bool
    {
        return $this->fulfilment_method === 'both' || $this->fulfilment_method === $fulfilmentMethod;
    }
}
