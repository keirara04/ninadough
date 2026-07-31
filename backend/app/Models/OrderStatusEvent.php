<?php

namespace App\Models;

use Database\Factories\OrderStatusEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'from_status', 'to_status', 'actor_type', 'actor_user_id', 'note_internal', 'metadata'])]
class OrderStatusEvent extends Model
{
    /** @use HasFactory<OrderStatusEventFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
