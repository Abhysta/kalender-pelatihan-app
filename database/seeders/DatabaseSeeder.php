<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@bpsdm.local'],
            [
                'name' => 'Admin BPSDM',
                'role' => User::ROLE_ADMIN,
                'password' => 'password',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'umum@bpsdm.local'],
            [
                'name' => 'User Umum',
                'role' => User::ROLE_UMUM,
                'password' => 'password',
            ]
        );
    }
}
