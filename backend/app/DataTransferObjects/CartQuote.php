<?php

namespace App\DataTransferObjects;

readonly class CartQuote
{
    /**
     * @param  array<int, array{
     *     product_id: int,
     *     product_variant_id: int|null,
     *     product_name: string,
     *     variant_name: string|null,
     *     unit_price_sen: int,
     *     quantity: int,
     *     capacity_units_each: int,
     *     line_total_sen: int,
     *     option_values: array<int, array{option_group_name: string, option_value_name: string}>,
     * }>  $lines
     */
    public function __construct(
        public array $lines,
        public int $subtotalSen,
        public int $deliveryFeeSen,
        public int $totalSen,
        public int $totalCapacityUnits,
        public int $preorderDateId,
        public string $orderDate,
        public int $remainingCapacity,
        public string $cutoffAt,
    ) {}

    public function toArray(): array
    {
        return [
            'lines' => $this->lines,
            'subtotal_sen' => $this->subtotalSen,
            'delivery_fee_sen' => $this->deliveryFeeSen,
            'total_sen' => $this->totalSen,
            'total_capacity_units' => $this->totalCapacityUnits,
            'preorder_date' => [
                'order_date' => $this->orderDate,
                'remaining_capacity' => $this->remainingCapacity,
                'cutoff_at' => $this->cutoffAt,
            ],
        ];
    }
}
