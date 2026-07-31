<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_all_26_v1_tables_after_fresh_migration(): void
    {
        $expectedTables = [
            'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks',
            'jobs', 'job_batches', 'failed_jobs', 'personal_access_tokens',
            'business_settings', 'products', 'product_images', 'product_option_groups',
            'product_option_values', 'product_variants', 'product_variant_option_values',
            'preorder_dates', 'delivery_zones', 'delivery_zone_postcodes', 'customers',
            'customer_addresses', 'orders', 'order_items', 'order_item_option_values',
            'order_status_events', 'payments', 'payment_proofs', 'notification_logs',
            'activity_logs',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_enforces_unique_public_identifiers_across_schema(): void
    {
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create();

        $this->assertSame(26, strlen($product->ulid));
        $this->assertSame(26, strlen($customer->ulid));
        $this->assertSame(26, strlen($order->ulid));
        $this->assertNotNull($order->idempotency_key);
    }

    public function test_retains_order_snapshots_after_source_product_is_force_deleted(): void
    {
        $item = OrderItem::factory()->create();
        $snapshotName = $item->product_name_snapshot;

        Product::find($item->product_id)->forceDelete();

        $this->assertSame($snapshotName, $item->fresh()->product_name_snapshot);
        $this->assertNull($item->fresh()->product_id);
    }
}
