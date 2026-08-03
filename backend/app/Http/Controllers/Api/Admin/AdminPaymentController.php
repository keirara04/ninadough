<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPaymentController extends Controller
{
    public function review(Request $request, Payment $payment)
    {
        $validated = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $order = $payment->order;
        $isApprove = $validated['action'] === 'approve';

        $payment->update(['status' => $isApprove ? 'confirmed' : 'failed']);

        $order->update([
            'status' => $isApprove ? 'payment_confirmed' : 'awaiting_payment',
            'payment_status' => $isApprove ? 'paid' : 'awaiting_payment',
            'paid_at' => $isApprove ? now() : null,
        ]);

        $payment->proofs()->latest('uploaded_at')->first()?->update([
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
            'review_note_internal' => $validated['note'] ?? null,
        ]);

        return new AdminOrderResource($order->fresh(['items', 'payments.proofs']));
    }
}
