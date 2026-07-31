<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'owner@ninadough.test'],
            [
                'name' => 'ninadough Owner',
                'password' => 'password',
                'role' => 'owner',
                'is_active' => true,
            ]
        );
    }
}
