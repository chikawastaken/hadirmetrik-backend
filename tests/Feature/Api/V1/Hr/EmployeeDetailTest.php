<?php

namespace Tests\Feature\Api\V1\Hr;

use App\Modules\Employees\Infrastructure\Models\EmployeeCompensation;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Modules\Employees\Infrastructure\Models\EmployeeShiftAssignment;
use App\Modules\Employees\Infrastructure\Models\EmployeeWorkLocationAssignment;
use App\Modules\Employees\Infrastructure\Models\InvitationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class EmployeeDetailTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_hr_can_view_employee_detail_with_active_records_and_invitation(): void
    {
        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $division = $this->makeDivision(['name' => 'Operasional', 'code' => 'OPS']);
        $shift = $this->makeShift(['name' => 'Pagi 08:00-17:00']);
        $location = $this->makeWorkLocation(['name' => 'Gudang Kalasan']);
        $manager = $this->makeEmployeeRecord([
            'employee_number' => 'HM-0012',
            'full_name' => 'Budi Santoso',
        ]);
        $oldShift = $this->makeShift(['name' => 'Shift Lama']);
        $oldLocation = $this->makeWorkLocation(['name' => 'Lokasi Lama']);

        $employeeUser = $this->makeEmployeeUser(['email' => 'putri.r@sinarmetrik.co.id']);
        $employee = $this->makeEmployeeRecord([
            'user_id' => $employeeUser->id,
            'employee_number' => 'HM-0251',
            'full_name' => 'Putri Ramadhani',
            'work_email' => 'putri.r@sinarmetrik.co.id',
            'phone_e164' => '+6281227453318',
            'division_id' => $division->id,
            'job_title' => 'Staf Admin Gudang',
            'manager_id' => $manager->id,
            'employment_start' => '2026-10-12',
            'employment_status' => 'probation',
            'face_registered_at' => now(),
        ]);

        EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'currency' => 'IDR',
            'base_salary' => 4800000,
            'transport_allowance' => 400000,
            'meal_allowance' => 550000,
            'effective_from' => now()->subMonth()->toDateString(),
            'created_by' => $hr->id,
        ]);

        EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'currency' => 'IDR',
            'base_salary' => 1000000,
            'transport_allowance' => 0,
            'meal_allowance' => 0,
            'effective_from' => now()->subYears(2)->toDateString(),
            'effective_to' => now()->subYear()->toDateString(),
            'created_by' => $hr->id,
        ]);

        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => now()->subMonth()->toDateString(),
            'created_by' => $hr->id,
        ]);

        EmployeeShiftAssignment::create([
            'employee_id' => $employee->id,
            'shift_id' => $oldShift->id,
            'effective_from' => now()->subYears(2)->toDateString(),
            'effective_to' => now()->subYear()->toDateString(),
            'created_by' => $hr->id,
        ]);

        EmployeeWorkLocationAssignment::create([
            'employee_id' => $employee->id,
            'work_location_id' => $location->id,
            'effective_from' => now()->subMonth()->toDateString(),
            'created_by' => $hr->id,
        ]);

        EmployeeWorkLocationAssignment::create([
            'employee_id' => $employee->id,
            'work_location_id' => $oldLocation->id,
            'effective_from' => now()->subYears(2)->toDateString(),
            'effective_to' => now()->subYear()->toDateString(),
            'created_by' => $hr->id,
        ]);

        EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'old-token'),
            'status' => 'revoked',
            'requested_channels' => ['email'],
            'expires_at' => now()->subDay(),
            'revoked_at' => now()->subDay(),
            'created_by' => $hr->id,
        ]);

        $invitation = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'new-token'),
            'status' => 'pending',
            'requested_channels' => ['email', 'whatsapp'],
            'expires_at' => now()->addDays(3),
            'created_by' => $hr->id,
        ]);

        InvitationDelivery::create([
            'invitation_id' => $invitation->id,
            'channel' => 'email',
            'recipient' => 'putri.r@sinarmetrik.co.id',
            'status' => 'queued',
            'attempt' => 1,
        ]);

        InvitationDelivery::create([
            'invitation_id' => $invitation->id,
            'channel' => 'whatsapp',
            'recipient' => '+6281227453318',
            'status' => 'delivered',
            'attempt' => 1,
        ]);

        $this->getJson("/api/v1/hr/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee.id', $employee->id)
            ->assertJsonPath('data.employee.employee_number', 'HM-0251')
            ->assertJsonPath('data.employee.full_name', 'Putri Ramadhani')
            ->assertJsonPath('data.employee.work_email', 'putri.r@sinarmetrik.co.id')
            ->assertJsonPath('data.employee.phone_e164', '+6281227453318')
            ->assertJsonPath('data.employee.division.id', $division->id)
            ->assertJsonPath('data.employee.division.name', 'Operasional')
            ->assertJsonPath('data.employee.job_title', 'Staf Admin Gudang')
            ->assertJsonPath('data.employee.manager.id', $manager->id)
            ->assertJsonPath('data.employee.manager.full_name', 'Budi Santoso')
            ->assertJsonPath('data.employee.employment_start', '2026-10-12')
            ->assertJsonPath('data.employee.employment_status', 'probation')
            ->assertJsonPath('data.compensation.base_salary', '4800000.00')
            ->assertJsonPath('data.compensation.transport_allowance', '400000.00')
            ->assertJsonPath('data.compensation.meal_allowance', '550000.00')
            ->assertJsonPath('data.compensation.total_monthly', '5750000.00')
            ->assertJsonPath('data.compensation.currency', 'IDR')
            ->assertJsonPath('data.shift_assignment.shift_id', $shift->id)
            ->assertJsonPath('data.shift_assignment.name', 'Pagi 08:00-17:00')
            ->assertJsonPath('data.work_location_assignment.work_location_id', $location->id)
            ->assertJsonPath('data.work_location_assignment.name', 'Gudang Kalasan')
            ->assertJsonPath('data.invitation.id', $invitation->id)
            ->assertJsonPath('data.invitation.status', 'pending')
            ->assertJsonPath('data.invitation.requested_channels', ['email', 'whatsapp'])
            ->assertJsonCount(2, 'data.invitation.deliveries')
            ->assertJsonPath('data.invitation.deliveries.0.channel', 'email')
            ->assertJsonPath('data.invitation.deliveries.0.status', 'queued')
            ->assertJsonPath('data.invitation.deliveries.1.channel', 'whatsapp')
            ->assertJsonPath('data.invitation.deliveries.1.status', 'delivered');
    }

    public function test_unknown_employee_returns_not_found(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $this->getJson('/api/v1/hr/employees/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_non_hr_cannot_view_employee_detail(): void
    {
        Sanctum::actingAs($this->makeEmployeeUser());
        $employee = $this->makeEmployeeRecord();

        $this->getJson("/api/v1/hr/employees/{$employee->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
