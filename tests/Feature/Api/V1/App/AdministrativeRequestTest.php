<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class AdministrativeRequestTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    private function employeeUser(): array
    {
        $user = $this->makeEmployeeUser();
        $division = $this->makeDivision(['name' => 'Operasional']);
        $employee = $this->makeEmployeeRecord([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'full_name' => 'Rina Pratiwi',
        ]);

        Sanctum::actingAs($user);

        return [$user, $employee];
    }

    private function cutiPayload(array $overrides = []): array
    {
        return array_merge([
            'request_type' => 'cuti',
            'category' => 'tahunan',
            'start_date' => '2026-11-23',
            'end_date' => '2026-11-25',
            'day_portion' => 'full',
            'reason' => 'Acara keluarga besar di luar kota.',
        ], $overrides);
    }

    public function test_employee_submits_annual_leave_and_two_step_approvals_are_created(): void
    {
        [, $employee] = $this->employeeUser();

        $response = $this->postJson('/api/v1/administrative-requests', $this->cutiPayload());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.request.request_type', 'cuti')
            ->assertJsonPath('data.request.status', 'submitted')
            ->assertJsonPath('data.request.day_count', 3)
            ->assertJsonPath('data.request.employee_name', 'Rina Pratiwi')
            ->assertJsonCount(2, 'data.request.approvals')
            ->assertJsonPath('data.request.approvals.0.approver_role', 'supervisor')
            ->assertJsonPath('data.request.approvals.1.approver_role', 'hr');

        $this->assertDatabaseHas('administrative_requests', [
            'employee_id' => $employee->id,
            'request_type' => 'cuti',
            'status' => 'submitted',
            'day_count' => 3,
        ]);
        $this->assertDatabaseCount('request_approvals', 2);
    }

    public function test_submission_requires_a_reason_of_at_least_ten_characters(): void
    {
        $this->employeeUser();

        $this->postJson('/api/v1/administrative-requests', $this->cutiPayload(['reason' => 'pendek']))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('reason');
    }

    public function test_annual_leave_cannot_exceed_remaining_quota(): void
    {
        [, $employee] = $this->employeeUser();

        AdministrativeRequest::create([
            'employee_id' => $employee->id,
            'request_type' => 'cuti',
            'category' => 'tahunan',
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-13',
            'day_portion' => 'full',
            'day_count' => 10,
            'reason' => 'Cuti panjang awal tahun.',
            'status' => 'approved',
            'submitted_at' => now(),
            'decided_at' => now(),
        ]);

        $this->postJson('/api/v1/administrative-requests', $this->cutiPayload())
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.code.0', 'QUOTA_EXCEEDED');
    }

    public function test_permission_requests_are_limited_per_month(): void
    {
        $this->employeeUser();

        $this->postJson('/api/v1/administrative-requests', [
            'request_type' => 'izin',
            'category' => 'pribadi',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'day_portion' => 'morning',
            'reason' => 'Mengurus dokumen kependudukan.',
        ])->assertStatus(201);

        $this->postJson('/api/v1/administrative-requests', [
            'request_type' => 'izin',
            'category' => 'pribadi',
            'start_date' => '2026-11-09',
            'end_date' => '2026-11-09',
            'day_portion' => 'morning',
            'reason' => 'Mengantar orang tua kontrol.',
        ])->assertStatus(201);

        $this->postJson('/api/v1/administrative-requests', [
            'request_type' => 'izin',
            'category' => 'pribadi',
            'start_date' => '2026-11-16',
            'end_date' => '2026-11-16',
            'day_portion' => 'morning',
            'reason' => 'Keperluan keluarga mendesak.',
        ])->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'QUOTA_EXCEEDED');
    }

    public function test_overtime_requires_valid_hours_and_has_no_day_count(): void
    {
        $this->employeeUser();

        $this->postJson('/api/v1/administrative-requests', [
            'request_type' => 'lembur',
            'overtime_date' => '2026-11-24',
            'overtime_hours' => 3.5,
            'reason' => 'Menyelesaikan rekonsiliasi stok akhir bulan.',
        ])->assertStatus(201)
            ->assertJsonPath('data.request.day_count', null)
            ->assertJsonPath('data.request.overtime_hours', 3.5);

        $this->postJson('/api/v1/administrative-requests', [
            'request_type' => 'lembur',
            'overtime_date' => '2026-11-25',
            'overtime_hours' => 14,
            'reason' => 'Menyelesaikan rekonsiliasi stok akhir bulan.',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('overtime_hours');
    }

    public function test_special_leave_is_limited_and_requires_a_reason(): void
    {
        $this->employeeUser();

        $this->postJson('/api/v1/administrative-requests', $this->cutiPayload([
            'category' => 'khusus',
            'start_date' => '2026-11-23',
            'end_date' => '2026-11-27',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('special_reason');

        $this->postJson('/api/v1/administrative-requests', $this->cutiPayload([
            'category' => 'khusus',
            'special_reason' => 'menikah',
            'start_date' => '2026-11-23',
            'end_date' => '2026-11-27',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    public function test_employee_sees_only_own_requests_and_summary(): void
    {
        [, $employee] = $this->employeeUser();

        $otherUser = $this->makeEmployeeUser();
        $other = $this->makeEmployeeRecord(['user_id' => $otherUser->id]);

        AdministrativeRequest::create([
            'employee_id' => $other->id,
            'request_type' => 'izin',
            'category' => 'sakit',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'day_portion' => 'full',
            'day_count' => 1,
            'reason' => 'Demam dan perlu istirahat.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->postJson('/api/v1/administrative-requests', $this->cutiPayload())->assertStatus(201);

        $this->getJson('/api/v1/administrative-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data.requests')
            ->assertJsonPath('data.requests.0.employee_id', $employee->id);

        $this->getJson('/api/v1/administrative-requests/summary')
            ->assertOk()
            ->assertJsonPath('data.summary.annual_quota_days', 12)
            ->assertJsonPath('data.summary.annual_remaining_days', 12)
            ->assertJsonPath('data.summary.izin_this_month', 0)
            ->assertJsonPath('data.summary.pending_count', 1);
    }

    public function test_employee_cancels_a_pending_request_but_not_a_decided_one(): void
    {
        [, $employee] = $this->employeeUser();

        $created = $this->postJson('/api/v1/administrative-requests', $this->cutiPayload())
            ->assertStatus(201)
            ->json('data.request.id');

        $this->postJson('/api/v1/administrative-requests/'.$created.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.request.status', 'cancelled');

        $decided = AdministrativeRequest::create([
            'employee_id' => $employee->id,
            'request_type' => 'cuti',
            'category' => 'tahunan',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-01',
            'day_portion' => 'full',
            'day_count' => 1,
            'reason' => 'Keperluan keluarga singkat.',
            'status' => 'approved',
            'submitted_at' => now(),
            'decided_at' => now(),
        ]);

        $this->postJson('/api/v1/administrative-requests/'.$decided->id.'/cancel')
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_user_without_employee_record_cannot_submit(): void
    {
        Sanctum::actingAs($this->makeEmployeeUser());

        $this->postJson('/api/v1/administrative-requests', $this->cutiPayload())
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
