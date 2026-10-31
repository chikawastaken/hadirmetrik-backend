<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\EmployeeCompensation;
use App\Modules\Employees\Infrastructure\Models\EmployeeShiftAssignment;
use App\Modules\Employees\Infrastructure\Models\EmployeeWorkLocationAssignment;
use App\Modules\Employees\Infrastructure\Models\Shift;
use App\Modules\Employees\Infrastructure\Models\WorkLocation;
use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_own_profile_returns_full_data(): void
    {
        $division = Division::create([
            'name' => 'HR & GA',
            'code' => 'HR',
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'timezone' => 'Asia/Jakarta',
            'is_overnight' => false,
            'is_active' => true,
        ]);

        $location = WorkLocation::create([
            'name' => 'Kantor Pusat',
            'address' => 'Jl. Magelang Km 5',
            'latitude' => -7.768123,
            'longitude' => 110.456789,
            'geofence_radius_m' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Putri Ramadhani',
            'email' => 'putri.r@hadirmetrik.local',
            'must_change_password' => false,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'HM-0251',
            'full_name' => 'Putri Ramadhani',
            'work_email' => 'putri.r@hadirmetrik.local',
            'phone_e164' => '+6281227453318',
            'division_id' => $division->id,
            'job_title' => 'Staf Admin Gudang',
            'employment_status' => 'probation',
            'employment_start' => '2026-10-12',
        ]);

        EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'base_salary' => 4800000,
            'transport_allowance' => 400000,
            'meal_allowance' => 550000,
            'currency' => 'IDR',
            'effective_from' => '2026-10-01',
            'effective_to' => null,
        ]);

        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => '2026-10-01',
            'effective_to' => null,
        ]);

        EmployeeWorkLocationAssignment::create([
            'employee_id' => $employee->id,
            'work_location_id' => $location->id,
            'effective_from' => '2026-10-01',
            'effective_to' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/profile');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee.id', $employee->id)
            ->assertJsonPath('data.employee.employee_number', 'HM-0251')
            ->assertJsonPath('data.employee.full_name', 'Putri Ramadhani')
            ->assertJsonPath('data.employee.work_email', 'putri.r@hadirmetrik.local')
            ->assertJsonPath('data.compensation.base_salary', '4800000.00')
            ->assertJsonPath('data.shift_assignment.shift_id', $shift->id)
            ->assertJsonPath('data.work_location_assignment.work_location_id', $location->id);
    }

    public function test_get_profile_requires_auth(): void
    {
        $this->getJson('/api/v1/me/profile')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
