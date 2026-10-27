<?php

namespace Tests\Feature\Api\V1\Hr;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class EmployeeOptionsTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_hr_can_fetch_active_form_options(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $this->makeDivision(['name' => 'Operasional', 'code' => 'OPS']);
        $this->makeDivision(['name' => 'Nonaktif', 'code' => 'OFF', 'is_active' => false]);

        $this->makeShift(['name' => 'Pagi 08:00-17:00']);
        $this->makeShift(['name' => 'Shift Nonaktif', 'is_active' => false]);

        $this->makeWorkLocation(['name' => 'Kantor Pusat']);
        $this->makeWorkLocation(['name' => 'Lokasi Nonaktif', 'is_active' => false]);

        $manager = $this->makeEmployeeRecord([
            'employee_number' => 'HM-0012',
            'full_name' => 'Budi Santoso',
            'job_title' => 'Supervisor',
        ]);
        $this->makeEmployeeRecord([
            'employee_number' => 'HM-0099',
            'full_name' => 'Mantan Karyawan',
            'employment_status' => 'terminated',
        ]);

        $response = $this->getJson('/api/v1/hr/employees/create-options');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.divisions')
            ->assertJsonPath('data.divisions.0.name', 'Operasional')
            ->assertJsonPath('data.divisions.0.code', 'OPS')
            ->assertJsonCount(1, 'data.shifts')
            ->assertJsonPath('data.shifts.0.name', 'Pagi 08:00-17:00')
            ->assertJsonCount(1, 'data.work_locations')
            ->assertJsonPath('data.work_locations.0.name', 'Kantor Pusat')
            ->assertJsonCount(1, 'data.managers')
            ->assertJsonPath('data.managers.0.id', $manager->id)
            ->assertJsonPath('data.managers.0.employee_number', 'HM-0012')
            ->assertJsonPath('data.managers.0.full_name', 'Budi Santoso')
            ->assertJsonPath('data.managers.0.job_title', 'Supervisor');
    }

    public function test_non_hr_cannot_fetch_create_options(): void
    {
        Sanctum::actingAs($this->makeEmployeeUser());

        $this->getJson('/api/v1/hr/employees/create-options')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
