<?php

namespace App\Actions\Inventory;

use App\Models\Order;
use App\Models\ProductVariant;

class ReleaseStockAction
{
    /**
     * Releases a pre-payment reservation on reject/expire/pre-payment-cancel.
     * Never touches stock_quantity — only reserved_quantity. Must be called
     * from within an outer DB::transaction().
     */
    public function execute(Order $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            $variant = ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first();

            if (! $variant) {
                continue;
            }

            $variant->reserved_quantity = max(0, $variant->reserved_quantity - $item->quantity);
            $variant->save();
        }
    }
}
