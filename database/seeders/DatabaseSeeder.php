<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application with development data.
     * Run: php artisan db:seed
     */
    public function run(): void
    {
        // HR admin account (password: admin123)
        User::firstOrCreate(
            ['email' => 'hr@hadirmetrik.local'],
            [
                'name' => 'HR Admin',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'admin123')),
            ]
        );

        // Development employee
        User::firstOrCreate(
            ['email' => 'employee@hadirmetrik.local'],
            [
                'name' => 'Karyawan Contoh',
                'password' => Hash::make(env('SEED_EMPLOYEE_PASSWORD', 'password')),
            ]
        );
    }
}
