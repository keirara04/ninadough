<?php

namespace App\Actions\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\ProductVariant;

class DeductStockAction
{
    /**
     * Converts a reservation made at order creation into a real deduction.
     * Since stock was already reserved, this cannot fail under normal
     * operation — the defensive re-check only guards against stock being
     * manually adjusted downward between order creation and confirm.
     * Must be called from within an outer DB::transaction().
     */
    public function execute(Order $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            $variant = ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first();

            if (! $variant || $variant->reserved_quantity < $item->quantity || $variant->stock_quantity < $item->quantity) {
                throw new InsufficientStockException('Insufficient stock — do not confirm yet.');
            }

            $variant->stock_quantity -= $item->quantity;
            $variant->reserved_quantity -= $item->quantity;
            $variant->save();
        }
    }
}
