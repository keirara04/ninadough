<?php

namespace App\Http\Controllers\Api;

use App\Actions\Checkout\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CreateOrderRequest;
use App\Http\Requests\Orders\OrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusResource;
use App\Models\Order;

class OrderController extends Controller
{
    public function store(CreateOrderRequest $request, CreateOrderAction $createOrderAction): OrderResource
    {
        $order = $createOrderAction->execute($request->validated());

        return new OrderResource($order);
    }

    public function status(OrderStatusRequest $request, string $reference): OrderStatusResource
    {
        $order = Order::where('order_number', $reference)
            ->orWhere('ulid', $reference)
            ->firstOrFail();

        return new OrderStatusResource($order);
    }
}
