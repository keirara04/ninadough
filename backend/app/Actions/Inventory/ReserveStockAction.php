<?php

namespace App\Actions\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\ProductVariant;

class ReserveStockAction
{
    /**
     * Must be called from within an outer DB::transaction() — the lock,
     * the reserved_quantity increment, and the order insert are one
     * atomic unit, mirroring ReservePreorderCapacityAction.
     *
     * @param  array<int, array{product_variant_id: int, quantity: int}>  $items
     */
    public function execute(array $items): void
    {
        foreach ($items as $item) {
            $variant = ProductVariant::whereKey($item['product_variant_id'])->lockForUpdate()->first();

            if (! $variant || $variant->stock_quantity - $variant->reserved_quantity < $item['quantity']) {
                throw new InsufficientStockException;
            }

            $variant->reserved_quantity += $item['quantity'];
            $variant->save();
        }
    }
}
