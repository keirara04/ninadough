<?php

namespace App\Http\Controllers\Api;

use App\Actions\Checkout\PriceCartAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutQuoteRequest;
use App\Http\Resources\CartQuoteResource;

class CheckoutQuoteController extends Controller
{
    public function store(CheckoutQuoteRequest $request, PriceCartAction $priceCartAction): CartQuoteResource
    {
        $data = $request->validated();

        $quote = $priceCartAction->execute(
            items: $data['items'],
            orderDate: $data['preorder_date'],
            fulfilmentMethod: $data['fulfilment_method'],
            deliveryZoneId: $data['delivery_zone_id'] ?? null,
        );

        return new CartQuoteResource($quote);
    }
}
