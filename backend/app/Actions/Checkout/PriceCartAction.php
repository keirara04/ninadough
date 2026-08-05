<?php

namespace App\Actions\Checkout;

use App\DataTransferObjects\CartQuote;
use App\Exceptions\PreorderDateUnavailableException;
use App\Models\PreorderDate;
use App\Models\ProductVariant;

class PriceCartAction
{
    public function __construct(
        private readonly ResolveDeliveryZoneAction $resolveDeliveryZoneAction = new ResolveDeliveryZoneAction,
    ) {}

    /**
     * @param  array<int, array{product_variant_id: int, quantity: int}>  $items
     */
    public function execute(
        array $items,
        string $orderDate,
        string $fulfilmentMethod,
        ?string $deliveryPostcode = null,
    ): CartQuote {
        $preorderDate = PreorderDate::whereDate('order_date', $orderDate)->first();

        if (! $preorderDate) {
            throw new PreorderDateUnavailableException;
        }

        $lines = [];
        $subtotalSen = 0;
        $totalCapacityUnits = 0;

        foreach ($items as $item) {
            $variant = ProductVariant::with(['product', 'optionValues.productOptionGroup'])
                ->findOrFail($item['product_variant_id']);

            abort_if(! $variant->is_active || ! $variant->product->is_active, 422, 'Selected product or variant is no longer available.');

            $quantity = $item['quantity'];
            $unitPriceSen = $variant->product->base_price_sen + $variant->price_adjustment_sen;
            $capacityUnitsEach = $variant->capacity_units ?? $variant->product->default_capacity_units;
            $lineTotalSen = $unitPriceSen * $quantity;

            $lines[] = [
                'product_id' => $variant->product->id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'unit_price_sen' => $unitPriceSen,
                'quantity' => $quantity,
                'capacity_units_each' => $capacityUnitsEach,
                'line_total_sen' => $lineTotalSen,
                'option_values' => $variant->optionValues->map(fn ($value) => [
                    'option_group_name' => $value->productOptionGroup->name,
                    'option_value_name' => $value->name,
                ])->all(),
            ];

            $subtotalSen += $lineTotalSen;
            $totalCapacityUnits += $capacityUnitsEach * $quantity;
        }

        $deliveryFeeSen = 0;
        $deliveryZoneId = null;
        if ($fulfilmentMethod === 'delivery') {
            $zone = $this->resolveDeliveryZoneAction->execute($deliveryPostcode);
            $deliveryFeeSen = $zone->delivery_fee_sen;
            $deliveryZoneId = $zone->id;
        }

        return new CartQuote(
            lines: $lines,
            subtotalSen: $subtotalSen,
            deliveryFeeSen: $deliveryFeeSen,
            totalSen: $subtotalSen + $deliveryFeeSen,
            totalCapacityUnits: $totalCapacityUnits,
            preorderDateId: $preorderDate->id,
            orderDate: $preorderDate->order_date->toDateString(),
            remainingCapacity: max(0, $preorderDate->capacity_limit - $preorderDate->reserved_capacity),
            cutoffAt: $preorderDate->cutoff_at->toIso8601String(),
            deliveryZoneId: $deliveryZoneId,
        );
    }
}
