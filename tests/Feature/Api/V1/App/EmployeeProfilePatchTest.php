<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfilePatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_own_profile_phone_and_job_title(): void
    {
        $division = Division::create([
            'name' => 'HR & GA',
            'code' => 'HR',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['must_change_password' => false]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'HM-0251',
            'full_name' => 'Putri Ramadhani',
            'work_email' => 'putri.r@hadirmetrik.local',
            'division_id' => $division->id,
            'job_title' => null,
            'employment_status' => 'probation',
            'employment_start' => '2026-10-12',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/me/profile', [
                'phone_e164' => '+6281234567890',
                'job_title' => 'Senior Staff',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee.phone_e164', '+6281234567890')
            ->assertJsonPath('data.employee.job_title', 'Senior Staff');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'phone_e164' => '+6281234567890',
            'job_title' => 'Senior Staff',
        ]);
    }

    public function test_update_profile_rejects_invalid_phone(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'HM-0252',
            'full_name' => 'Test',
            'work_email' => $user->email,
            'employment_status' => 'probation',
            'employment_start' => '2026-10-12',
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/me/profile', ['phone_e164' => 'invalid'])
            ->assertUnprocessable();
    }
}
