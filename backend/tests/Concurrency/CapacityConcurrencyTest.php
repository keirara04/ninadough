<?php

namespace Tests\Concurrency;

use App\Actions\Checkout\CreateOrderAction;
use App\Exceptions\PreorderDateFullException;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

/**
 * Proves the spec §8 capacity-safe reservation design against a real
 * Postgres database using real forked OS processes — a single PHPUnit
 * process cannot exercise two genuinely concurrent transactions.
 *
 * Run with: vendor/bin/phpunit -c phpunit.concurrency.xml
 * Requires a real Postgres database named `ninadough_concurrency_test`
 * (same server/credentials as .env) — see phpunit.concurrency.xml.
 */
class CapacityConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension is not available.');
        }

        $this->artisan('migrate:fresh')->run();
    }

    protected function tearDown(): void
    {
        DB::table('order_item_option_values')->delete();
        DB::table('order_items')->delete();
        DB::table('order_status_events')->delete();
        DB::table('orders')->delete();
        DB::table('customers')->delete();
        DB::table('product_variant_option_values')->delete();
        DB::table('product_variants')->delete();
        DB::table('products')->delete();
        DB::table('preorder_dates')->delete();

        parent::tearDown();
    }

    public function test_only_one_concurrent_checkout_claims_the_last_capacity_unit(): void
    {
        $product = Product::factory()->create(['base_price_sen' => 1000]);
        $variant = ProductVariant::factory()->for($product)->create(['capacity_units' => 1, 'price_adjustment_sen' => 0]);
        $preorderDate = PreorderDate::factory()->create([
            'capacity_limit' => 5,
            'reserved_capacity' => 4,
            'cutoff_at' => now()->addDay(),
        ]);

        $childCount = 3;
        $resultDir = sys_get_temp_dir().'/ninadough-concurrency-'.uniqid();
        mkdir($resultDir);

        // Close the parent's DB connection before forking so children don't
        // inherit and fight over the same live socket.
        DB::disconnect();

        $pids = [];

        for ($i = 0; $i < $childCount; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Could not fork child process.');
            }

            if ($pid === 0) {
                $this->runChild($i, $variant->id, $preorderDate->order_date->toDateString(), $resultDir);
                exit(0);
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        DB::reconnect();

        $results = [];
        for ($i = 0; $i < $childCount; $i++) {
            $results[] = trim(@file_get_contents("{$resultDir}/{$i}.result") ?: 'missing');
        }
        exec('rm -rf '.escapeshellarg($resultDir));

        $successes = array_filter($results, fn ($r) => $r === 'success');
        $fulls = array_filter($results, fn ($r) => $r === 'full');
        $unexpected = array_filter($results, fn ($r) => ! in_array($r, ['success', 'full'], true));

        $this->assertSame([], array_values($unexpected), 'Unexpected child result(s): '.implode(' | ', $unexpected));
        $this->assertCount(1, $successes, 'Exactly one child should have won the last capacity slot. Results: '.implode(', ', $results));
        $this->assertCount($childCount - 1, $fulls, 'The remaining children should have been rejected as full.');

        $finalDate = PreorderDate::find($preorderDate->id);
        $this->assertSame(5, $finalDate->reserved_capacity);
        $this->assertLessThanOrEqual($finalDate->capacity_limit, $finalDate->reserved_capacity);
    }

    private function runChild(int $index, int $variantId, string $orderDate, string $resultDir): void
    {
        // Child process: force a brand new physical connection, never reuse
        // the parent's (already-closed) socket/handle.
        DB::purge();

        $resultFile = "{$resultDir}/{$index}.result";

        try {
            (new CreateOrderAction)->execute([
                'items' => [['product_variant_id' => $variantId, 'quantity' => 1]],
                'preorder_date' => $orderDate,
                'checkout_channel' => 'website',
                'fulfilment_method' => 'pickup',
                'idempotency_key' => (string) Str::uuid(),
                'customer' => [
                    'name' => "Concurrency Child {$index}",
                    'phone_e164' => '+601'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                ],
            ]);

            file_put_contents($resultFile, 'success');
        } catch (PreorderDateFullException) {
            file_put_contents($resultFile, 'full');
        } catch (Throwable $e) {
            file_put_contents($resultFile, 'error: '.$e->getMessage());
        }
    }
}
