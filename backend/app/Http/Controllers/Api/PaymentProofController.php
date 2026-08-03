<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\UploadPaymentProofRequest;
use App\Http\Resources\OrderStatusResource;
use App\Models\Order;
use App\Models\PaymentProof;

class PaymentProofController extends Controller
{
    public function store(UploadPaymentProofRequest $request, string $reference): OrderStatusResource
    {
        $order = Order::where('order_number', $reference)
            ->orWhere('ulid', $reference)
            ->firstOrFail();

        $payment = $order->payments()->where('status', 'pending')->latest()->firstOrFail();

        $file = $request->file('proof');
        $path = $file->store("payment-proofs/{$order->ulid}", 'public');

        PaymentProof::create([
            'payment_id' => $payment->id,
            'storage_disk' => 'public',
            'object_key' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_at' => now(),
        ]);

        $payment->update(['status' => 'submitted']);
        $order->update(['status' => 'payment_submitted', 'payment_status' => 'submitted']);

        return new OrderStatusResource($order);
    }
}
