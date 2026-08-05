<?php

namespace App\Models;

use Database\Factories\PreorderDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_date', 'cutoff_at', 'capacity_limit', 'reserved_capacity',
    'pickup_enabled', 'delivery_enabled', 'status', 'note_internal', 'created_by_user_id',
])]
class PreorderDate extends Model
{
    /** @use HasFactory<PreorderDateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'cutoff_at' => 'datetime',
            'pickup_enabled' => 'boolean',
            'delivery_enabled' => 'boolean',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class);
    }
}
