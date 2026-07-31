<?php

namespace Tests\Feature\Schema;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_unique_26_character_ulid_and_defaults_role_is_active(): void
    {
        $user = User::factory()->create();

        $this->assertIsString($user->ulid);
        $this->assertEquals(26, strlen($user->ulid));
        $this->assertSame('owner', $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_rejects_second_user_with_duplicate_ulid(): void
    {
        $first = User::factory()->create();

        $this->expectException(QueryException::class);
        User::factory()->create(['ulid' => $first->ulid]);
    }
}
