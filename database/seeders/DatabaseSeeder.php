<?php

namespace Database\Seeders;

use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\Shift;
use App\Modules\Employees\Infrastructure\Models\WorkLocation;
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

        $admin = User::updateOrCreate(
            ['email' => 'hr@hadirmetrik.local'],
            [
                'name' => 'HR Admin',
                'password' => Hash::make($adminPassword),
                'role' => 'hr',
                'status' => 'active',
                'must_change_password' => false,
            ]
        );

        $division = Division::firstOrCreate(
            ['code' => 'OPS'],
            ['name' => 'Operasional', 'is_active' => true]
        );

        $shift = Shift::firstOrCreate(
            ['name' => 'Pagi 08:00-17:00'],
            [
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'timezone' => 'Asia/Jakarta',
                'is_overnight' => false,
                'is_active' => true,
            ]
        );

        $location = WorkLocation::firstOrCreate(
            ['name' => 'Kantor Pusat'],
            [
                'address' => 'Jl. Contoh No. 1',
                'latitude' => -7.797068,
                'longitude' => 110.370529,
                'geofence_radius_m' => 100,
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]
        );

        $employeeNumber = (string) env('SEED_EMPLOYEE_NUMBER', 'HM-0001');
        $employeeEmail = (string) env('SEED_EMPLOYEE_EMAIL', 'employee@hadirmetrik.local');

        $employee = User::updateOrCreate(
            ['email' => $employeeEmail],
            [
                'name' => 'Karyawan Contoh',
                'password' => Hash::make($employeePassword),
                'role' => 'employee',
                'status' => 'active',
                'must_change_password' => false,
            ]
        );

        Employee::updateOrCreate(
            ['work_email' => $employeeEmail],
            [
                'user_id' => $employee->id,
                'employee_number' => $employeeNumber,
                'full_name' => 'Karyawan Contoh',
                'division_id' => $division->id,
                'job_title' => 'Staf Operasional',
                'employment_start' => now()->subYear()->toDateString(),
                'employment_status' => 'active',
            ]
        );

        $this->command?->info(sprintf(
            'Seeded HR account %s and employee %s (%s, shift %s, location %s, admin id %d).',
            $admin->email,
            $employeeEmail,
            $employeeNumber,
            $shift->name,
            $location->name,
            $admin->id,
        ));
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
