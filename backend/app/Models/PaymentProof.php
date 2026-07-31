<?php

namespace App\Models;

use Database\Factories\PaymentProofFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'payment_id', 'storage_disk', 'object_key', 'mime_type', 'size_bytes',
    'uploaded_at', 'reviewed_at', 'reviewed_by_user_id', 'review_note_internal',
])]
class PaymentProof extends Model
{
    /** @use HasFactory<PaymentProofFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
