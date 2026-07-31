<?php

namespace Tests\Feature\Schema;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomersTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_two_customers_with_same_phone_e164(): void
    {
        Customer::factory()->create(['phone_e164' => '+60123456789']);

        $this->expectException(QueryException::class);
        Customer::factory()->create(['phone_e164' => '+60123456789']);
    }

    public function test_only_allows_one_default_address_per_customer(): void
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->for($customer)->create(['is_default' => true]);

        $this->expectException(QueryException::class);
        CustomerAddress::factory()->for($customer)->create(['is_default' => true]);
    }
}
