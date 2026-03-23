<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed users used by local frontend testing.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        User::updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student User',
                'password' => $password,
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'company@example.com'],
            [
                'name' => 'Company User',
                'password' => $password,
                'role' => 'company',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => $password,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'unverified.student@example.com'],
            [
                'name' => 'Unverified Student',
                'password' => $password,
                'role' => 'student',
                'email_verified_at' => null,
            ]
        );

        User::factory(10)->create();
    }
}
