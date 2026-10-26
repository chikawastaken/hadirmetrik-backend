<?php

namespace Database\Seeders;

use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\InvitationCode;
use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application with development data.
     * Run: php artisan db:seed
     *
     * Passwords are never hardcoded: they must come from the environment
     * (SEED_ADMIN_PASSWORD, SEED_EMPLOYEE_PASSWORD) so no credential lands in
     * version control.
     */
    public function run(): void
    {
        $adminPassword = $this->requiredEnv('SEED_ADMIN_PASSWORD');
        $employeePassword = $this->requiredEnv('SEED_EMPLOYEE_PASSWORD');

        User::firstOrCreate(
            ['email' => 'hr@hadirmetrik.local'],
            [
                'name' => 'HR Admin',
                'password' => Hash::make($adminPassword),
                'role' => 'hr',
            ]
        );

        $employeeNumber = (string) env('SEED_EMPLOYEE_NUMBER', 'EMP-0001');
        $employeeEmail = (string) env('SEED_EMPLOYEE_EMAIL', 'employee@hadirmetrik.local');

        Employee::firstOrCreate(
            ['work_email' => $employeeEmail],
            [
                'employee_number' => $employeeNumber,
                'name' => 'Karyawan Contoh',
                'employment_status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => $employeeEmail],
            [
                'name' => 'Karyawan Contoh',
                'password' => Hash::make($employeePassword),
                'role' => 'employee',
            ]
        );

        $invitationCode = env('SEED_INVITATION_CODE');

        if (is_string($invitationCode) && $invitationCode !== '') {
            InvitationCode::firstOrCreate(
                ['code' => $invitationCode],
                ['is_used' => false],
            );

            $this->command?->info("Invitation code available: {$invitationCode}");
        } else {
            $this->command?->warn(
                'SEED_INVITATION_CODE is not set; no invitation code was created.'
            );
        }
    }

    private function requiredEnv(string $key): string
    {
        $value = env($key);

        if (! is_string($value) || $value === '') {
            throw new RuntimeException(
                "Seeder requires the {$key} environment variable to create development accounts."
            );
        }

        return $value;
    }
}
