<?php

namespace Tests\Feature\Api\V1\Hr;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class ResetEmployeePasswordTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_hr_can_reset_an_employee_password(): void
    {
        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $employeeUser = $this->makeEmployeeUser([
            'email' => 'putri.r@sinarmetrik.co.id',
            'password' => 'passwordLama123',
            'must_change_password' => false,
        ]);

        $employee = $this->makeEmployeeRecord([
            'user_id' => $employeeUser->id,
            'employee_number' => 'HM-0251',
            'work_email' => 'putri.r@sinarmetrik.co.id',
        ]);

        $response = $this->postJson("/api/v1/hr/employees/{$employee->id}/reset-password");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.employee_number', 'HM-0251')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonStructure(['data' => ['initial_password']]);

        $initialPassword = (string) $response->json('data.initial_password');

        $this->assertNotEmpty($initialPassword);
        $this->assertTrue(Hash::check($initialPassword, $employeeUser->fresh()->password));
        $this->assertTrue($employeeUser->fresh()->must_change_password);
        $this->assertFalse(Hash::check('passwordLama123', $employeeUser->fresh()->password));

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'employee',
            'entity_id' => (string) $employee->id,
            'action' => 'employee.password_reset',
            'actor_user_id' => $hr->id,
        ]);
    }

    public function test_employee_without_an_account_cannot_be_reset(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $employee = $this->makeEmployeeRecord(['user_id' => null]);

        $this->postJson("/api/v1/hr/employees/{$employee->id}/reset-password")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.employee.0', 'Karyawan belum memiliki akun.');
    }

    public function test_non_hr_cannot_reset_an_employee_password(): void
    {
        Sanctum::actingAs($this->makeEmployeeUser());

        $employee = $this->makeEmployeeRecord();

        $this->postJson("/api/v1/hr/employees/{$employee->id}/reset-password")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_reset_for_unknown_employee_returns_not_found(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $this->postJson('/api/v1/hr/employees/999999/reset-password')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
