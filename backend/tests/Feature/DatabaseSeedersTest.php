<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\PreorderDate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_owner_settings_products_dates_zones_and_demo_orders(): void
    {
        Artisan::call('db:seed');

        $this->assertTrue(User::where('role', 'owner')->exists());
        $this->assertTrue(BusinessSetting::where('key', 'business_name')->exists());
        $this->assertGreaterThan(0, Product::count());
        $this->assertGreaterThan(0, PreorderDate::count());
        $this->assertGreaterThan(0, DeliveryZone::count());
        $this->assertGreaterThan(0, Order::count());
    }

    public function test_seeds_orders_covering_every_lifecycle_status(): void
    {
        Artisan::call('db:seed');

        $seededStatuses = Order::pluck('status')->unique()->all();

        foreach (['awaiting_payment', 'payment_confirmed', 'completed', 'cancelled'] as $status) {
            $this->assertContains($status, $seededStatuses);
        }
    }
}
