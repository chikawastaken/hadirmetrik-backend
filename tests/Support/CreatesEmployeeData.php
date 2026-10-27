<?php

namespace Tests\Support;

use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\Shift;
use App\Modules\Employees\Infrastructure\Models\WorkLocation;
use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Support\Str;

trait CreatesEmployeeData
{
    protected function makeHrUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'hr',
            'status' => 'active',
            'must_change_password' => false,
        ], $attributes));
    }

    protected function makeEmployeeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
        ], $attributes));
    }

    protected function makeDivision(array $attributes = []): Division
    {
        return Division::create(array_merge([
            'name' => 'Divisi '.Str::random(6),
            'code' => 'D'.Str::upper(Str::random(6)),
            'is_active' => true,
        ], $attributes));
    }

    protected function makeShift(array $attributes = []): Shift
    {
        return Shift::create(array_merge([
            'name' => 'Shift '.Str::random(6),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'timezone' => 'Asia/Jakarta',
            'is_overnight' => false,
            'is_active' => true,
        ], $attributes));
    }

    protected function makeWorkLocation(array $attributes = []): WorkLocation
    {
        return WorkLocation::create(array_merge([
            'name' => 'Lokasi '.Str::random(6),
            'address' => 'Jl. Contoh No. 1',
            'latitude' => -7.797068,
            'longitude' => 110.370529,
            'geofence_radius_m' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ], $attributes));
    }

    protected function makeEmployeeRecord(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_number' => 'HM-'.Str::upper(Str::random(6)),
            'full_name' => 'Karyawan '.Str::random(4),
            'work_email' => Str::lower(Str::random(10)).'@hadirmetrik.local',
            'employment_status' => 'active',
        ], $attributes));
    }
}
