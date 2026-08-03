<?php

namespace App\Http\Controllers\Api\Admin;

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
        $order->load(['items', 'payments.proofs']);

        return new AdminOrderResource($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_STATUSES)],
        ])->validate();

        $order->update(['status' => $validated['status']]);

        return new AdminOrderResource($order->fresh(['items', 'payments.proofs']));
    }
}
