<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Orders\TransitionOrderStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPaymentController extends Controller
{
    public function review(Request $request, Payment $payment, TransitionOrderStatusAction $transitionAction)
    {
        $validated = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:500'],
            'rejection_message' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ])->validate();

        $isApprove = $validated['action'] === 'approve';
        $order = $payment->order;

        $order = $transitionAction->execute(
            orderId: $order->id,
            toStatus: $isApprove ? 'payment_confirmed' : 'rejected',
            actorType: 'user',
            actorUser: $request->user(),
            rejectionMessage: $isApprove ? null : $validated['rejection_message'],
            noteInternal: $validated['note'] ?? null,
        );

        $payment->update(['status' => $isApprove ? 'confirmed' : 'failed']);

        $payment->proofs()->latest('uploaded_at')->first()?->update([
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $request->user()->id,
            'review_note_internal' => $validated['note'] ?? null,
        ]);

        return new AdminOrderResource($order->fresh(['items', 'payments.proofs']));
    }
}
