<?php

namespace Tests\Feature\Api\V1\Hr;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class ListEmployeesTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_hr_can_list_employees(): void
    {
        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $division = $this->makeDivision(['name' => 'Operasional', 'code' => 'OPS']);
        $emp1 = $this->makeEmployeeRecord([
            'employee_number' => 'HM-0001',
            'full_name' => 'Rina Pratiwi',
            'work_email' => 'rina@example.com',
            'division_id' => $division->id,
            'job_title' => 'Staf Gudang',
        ]);
        $emp2 = $this->makeEmployeeRecord([
            'employee_number' => 'HM-0002',
            'full_name' => 'Budi Santoso',
            'work_email' => 'budi@example.com',
            'division_id' => $division->id,
            'job_title' => 'Supervisor',
        ]);

        $response = $this->getJson('/api/v1/hr/employees');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.employees');
    }

    public function test_hr_can_filter_employees_by_search_and_division(): void
    {
        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $divOps = $this->makeDivision(['name' => 'Operasional', 'code' => 'OPS']);
        $divSales = $this->makeDivision(['name' => 'Penjualan', 'code' => 'SLS']);

        $this->makeEmployeeRecord([
            'employee_number' => 'HM-0001',
            'full_name' => 'Rina Pratiwi',
            'division_id' => $divOps->id,
            'job_title' => 'Staf Gudang',
        ]);
        $this->makeEmployeeRecord([
            'employee_number' => 'HM-0002',
            'full_name' => 'Ahmad Penjualan',
            'division_id' => $divSales->id,
            'job_title' => 'Account Executive',
        ]);

        $resSearch = $this->getJson('/api/v1/hr/employees?search=Rina');
        $resSearch->assertOk()
            ->assertJsonCount(1, 'data.employees')
            ->assertJsonPath('data.employees.0.full_name', 'Rina Pratiwi');

        $resDiv = $this->getJson('/api/v1/hr/employees?division_id='.$divSales->id);
        $resDiv->assertOk()
            ->assertJsonCount(1, 'data.employees')
            ->assertJsonPath('data.employees.0.full_name', 'Ahmad Penjualan');
    }

    public function test_employee_role_cannot_list_hr_employees(): void
    {
        $employee = $this->makeEmployeeUser();
        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/v1/hr/employees');
        $response->assertForbidden();
    }
}
