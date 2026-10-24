<?php

namespace Tests\Feature\Api\V1\Hr;

use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Shift;
use App\Modules\Employees\Infrastructure\Models\WorkLocation;
use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateEmployeeTest extends TestCase
{
    use RefreshDatabase;

    private User $hrUser;

    private Division $division;

    private Shift $shift;

    private WorkLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrUser = User::factory()->create([
            'role' => 'hr',
            'status' => 'active',
        ]);

        $this->division = Division::create([
            'name' => 'Operasional',
            'code' => 'OPS',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Pagi 08:00–17:00',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'timezone' => 'Asia/Jakarta',
            'is_overnight' => false,
            'is_active' => true,
        ]);

        $this->location = WorkLocation::create([
            'name' => 'Gudang Kalasan',
            'address' => 'Jl. Kalasan No. 10',
            'latitude' => -7.768123,
            'longitude' => 110.456789,
            'geofence_radius_m' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);
    }

    public function test_hr_can_create_employee_with_auto_generated_credentials_and_invitations(): void
    {
        Sanctum::actingAs($this->hrUser);

        $payload = [
            'full_name' => 'Putri Ramadhani',
            'email' => 'putri.r@sinarmetrik.co.id',
            'phone' => '081227453318',
            'division_id' => $this->division->id,
            'job_title' => 'Staf Admin Gudang',
            'employment_start' => '2026-10-12',
            'employment_status' => 'probation',
            'shift_id' => $this->shift->id,
            'work_location_id' => $this->location->id,
            'compensation' => [
                'base_salary' => 4800000,
                'transport_allowance' => 400000,
                'meal_allowance' => 550000,
            ],
            'send_invitation' => true,
            'invitation_channels' => ['email', 'whatsapp'],
            'invitation_expires_in_days' => 3,
        ];

        $response = $this->postJson('/api/v1/hr/employees', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee.full_name', 'Putri Ramadhani')
            ->assertJsonPath('data.employee.work_email', 'putri.r@sinarmetrik.co.id')
            ->assertJsonPath('data.employee.phone_e164', '+6281227453318')
            ->assertJsonPath('data.employee.employee_number', 'HM-0001')
            ->assertJsonPath('data.compensation.total_monthly', '5750000.00')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.invitation.status', 'pending');

        $this->assertNotEmpty($response->json('data.initial_password'));

        // Assert database assertions
        $this->assertDatabaseHas('users', [
            'email' => 'putri.r@sinarmetrik.co.id',
            'role' => 'employee',
            'must_change_password' => true,
        ]);

        $this->assertDatabaseHas('employees', [
            'employee_number' => 'HM-0001',
            'full_name' => 'Putri Ramadhani',
            'work_email' => 'putri.r@sinarmetrik.co.id',
            'phone_e164' => '+6281227453318',
        ]);

        $this->assertDatabaseHas('employee_compensations', [
            'base_salary' => 4800000,
            'transport_allowance' => 400000,
            'meal_allowance' => 550000,
        ]);

        $this->assertDatabaseHas('employee_shift_assignments', [
            'shift_id' => $this->shift->id,
        ]);

        $this->assertDatabaseHas('employee_work_location_assignments', [
            'work_location_id' => $this->location->id,
        ]);

        $this->assertDatabaseHas('employee_invitations', [
            'purpose' => 'face_enrollment',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('invitation_deliveries', [
            'channel' => 'email',
            'recipient' => 'putri.r@sinarmetrik.co.id',
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('invitation_deliveries', [
            'channel' => 'whatsapp',
            'recipient' => '+6281227453318',
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.created',
        ]);
    }

    public function test_non_hr_cannot_create_employee(): void
    {
        $employeeUser = User::factory()->create([
            'role' => 'employee',
        ]);

        Sanctum::actingAs($employeeUser);

        $response = $this->postJson('/api/v1/hr/employees', [
            'full_name' => 'Karyawan Baru',
            'email' => 'baru@hadirmetrik.local',
        ]);

        $response->assertStatus(403);
    }

    public function test_employee_creation_returns_validation_error_for_inactive_division(): void
    {
        Sanctum::actingAs($this->hrUser);

        $response = $this->postJson('/api/v1/hr/employees', [
            'full_name' => 'Karyawan Baru',
            'email' => 'baru@hadirmetrik.local',
            'division_id' => 9999,
            'job_title' => 'Staf',
            'employment_start' => '2026-10-12',
            'shift_id' => $this->shift->id,
            'work_location_id' => $this->location->id,
            'compensation' => ['base_salary' => 4800000],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Selected division is not found or inactive.')
            ->assertJsonPath('errors.division_id.0', 'Selected division is not found or inactive.');
    }
}
