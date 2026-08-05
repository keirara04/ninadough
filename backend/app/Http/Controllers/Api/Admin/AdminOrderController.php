<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Orders\TransitionOrderStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminOrderController extends Controller
{
    private const ALLOWED_STATUSES = [
        'whatsapp_pending', 'awaiting_payment', 'payment_submitted', 'payment_confirmed',
        'preparing', 'ready_for_pickup', 'out_for_delivery', 'completed', 'cancelled', 'rejected', 'expired',
    ];

    private const MAX_PER_PAGE = 50;

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), self::MAX_PER_PAGE);

        $orders = Order::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return AdminOrderResource::collection($orders);
    }

    public function show(Order $order): AdminOrderResource
    {
        $order->load(['items', 'payments.proofs', 'timeSlot']);

        return new AdminOrderResource($order);
    }

    public function updateStatus(Request $request, Order $order, TransitionOrderStatusAction $transitionAction)
    {
        $validated = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_STATUSES)],
            'rejection_message' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $order = $transitionAction->execute(
            orderId: $order->id,
            toStatus: $validated['status'],
            actorType: 'user',
            actorUser: $request->user(),
            rejectionMessage: $validated['rejection_message'] ?? null,
            noteInternal: $validated['note'] ?? null,
        );

        return new AdminOrderResource($order->fresh(['items', 'payments.proofs', 'timeSlot']));
    }
}
