<?php

namespace App\Http\Controllers\Api;

use App\Actions\Orders\TransitionOrderStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\UploadPaymentProofRequest;
use App\Http\Resources\OrderStatusResource;
use App\Jobs\NotifyAdminPaymentSubmitted;
use App\Models\Order;
use App\Models\PaymentProof;
use Illuminate\Support\Facades\DB;

class PaymentProofController extends Controller
{
    public function store(
        UploadPaymentProofRequest $request,
        string $reference,
        TransitionOrderStatusAction $transitionAction,
    ): OrderStatusResource {
        $order = Order::where('order_number', $reference)
            ->orWhere('ulid', $reference)
            ->firstOrFail();

        // A payment already confirmed or rejected is a closed decision — no re-upload past that point.
        $payment = $order->payments()->whereIn('status', ['pending', 'submitted'])->latest()->firstOrFail();

        DB::transaction(function () use ($request, $order, $payment) {
            $payment->proofs()->whereNull('superseded_at')->update(['superseded_at' => now()]);

            $file = $request->file('proof');
            $path = $file->store("payment-proofs/{$order->ulid}", 'local');

            PaymentProof::create([
                'payment_id' => $payment->id,
                'storage_disk' => 'local',
                'object_key' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_at' => now(),
            ]);

            $payment->update(['status' => 'submitted']);
        });

        $order = $transitionAction->execute(
            orderId: $order->id,
            toStatus: 'payment_submitted',
            actorType: 'system',
        );

        NotifyAdminPaymentSubmitted::dispatch($order->id);

        return new OrderStatusResource($order);
    }
}
