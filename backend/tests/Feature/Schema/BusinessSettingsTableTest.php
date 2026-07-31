<?php

namespace Tests\Feature\Schema;

use App\Models\BusinessSetting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSettingsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_jsonb_value_and_casts_back_to_array(): void
    {
        $setting = BusinessSetting::factory()->create([
            'key' => 'business_name',
            'value' => ['name' => 'ninadough'],
        ]);

        $this->assertSame(['name' => 'ninadough'], $setting->fresh()->value);
    }

    public function test_rejects_duplicate_key(): void
    {
        BusinessSetting::factory()->create(['key' => 'timezone']);

        $this->expectException(QueryException::class);
        BusinessSetting::factory()->create(['key' => 'timezone']);
    }
}
