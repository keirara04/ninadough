<?php

namespace Tests\Feature\Schema;

use App\Models\OrderItem;
use App\Models\OrderItemOptionValue;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_keeps_order_item_row_when_source_product_is_deleted(): void
    {
        $item = OrderItem::factory()->create();
        $productId = $item->product_id;

        Product::find($productId)->forceDelete();

        $this->assertNull($item->fresh()->product_id);
        $this->assertNotNull($item->fresh()->product_name_snapshot);
    }

    public function test_rejects_quantity_of_zero(): void
    {
        $this->expectException(QueryException::class);
        OrderItem::factory()->create(['quantity' => 0]);
    }

    public function test_deletes_option_value_snapshots_when_order_item_is_deleted(): void
    {
        $item = OrderItem::factory()->create();
        $optionValue = OrderItemOptionValue::factory()->for($item, 'orderItem')->create();

        $item->delete();

        $this->assertNull(OrderItemOptionValue::find($optionValue->id));
    }
}
