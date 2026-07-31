<?php

namespace Tests\Feature\Schema;

use App\Models\PreorderDate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreorderDatesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_second_row_for_same_order_date(): void
    {
        PreorderDate::factory()->create(['order_date' => '2026-08-15']);

        $this->expectException(QueryException::class);
        PreorderDate::factory()->create(['order_date' => '2026-08-15']);
    }

    public function test_rejects_reserved_capacity_greater_than_capacity_limit(): void
    {
        $this->expectException(QueryException::class);
        PreorderDate::factory()->create([
            'capacity_limit' => 10,
            'reserved_capacity' => 11,
        ]);
    }

    public function test_rejects_negative_reserved_capacity(): void
    {
        $this->expectException(QueryException::class);
        PreorderDate::factory()->create(['reserved_capacity' => -1]);
    }
}
