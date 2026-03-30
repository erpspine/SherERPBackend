<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed users with known credentials for local testing.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@sher.local'],
            [
                'name' => 'Admin User',
                'phone' => '+255 700 000 001',
                'role' => 'Admin',
                'status' => 'Active',
                'password' => 'Admin@12345',
            ]
        );

        $admin->syncRoles(['Admin']);

        $staff = User::updateOrCreate(
            ['email' => 'staff@sher.local'],
            [
                'name' => 'Staff User',
                'phone' => '+255 700 000 002',
                'role' => 'Operations',
                'status' => 'Inactive',
                'password' => 'Staff@12345',
            ]
        );

        $staff->syncRoles(['Operations']);
    }
}
