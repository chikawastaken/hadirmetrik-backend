<?php

namespace Tests\Feature\Api\V1\Hr;

use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use App\Modules\Leave\Infrastructure\Models\RequestApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class ApprovalRequestTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    private function submittedCuti(array $employeeAttributes = []): array
    {
        $managerUser = $this->makeHrUser();
        $manager = $this->makeEmployeeRecord(['user_id' => $managerUser->id, 'full_name' => 'Dimas Ardiansyah']);

        $user = $this->makeEmployeeUser();
        $division = $this->makeDivision(['name' => 'Operasional']);
        $employee = $this->makeEmployeeRecord(array_merge([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'manager_id' => $manager->id,
            'full_name' => 'Rina Pratiwi',
        ], $employeeAttributes));

        Sanctum::actingAs($user);
        $id = $this->postJson('/api/v1/administrative-requests', [
            'request_type' => 'cuti',
            'category' => 'tahunan',
            'start_date' => '2026-11-23',
            'end_date' => '2026-11-25',
            'day_portion' => 'full',
            'reason' => 'Acara keluarga besar di luar kota.',
        ])->assertStatus(201)->json('data.request.id');

        return [$id, $employee, $managerUser, $user];
    }

    public function test_hr_queue_lists_pending_requests_with_employee_and_conflicts(): void
    {
        [$id, $employee] = $this->submittedCuti();

        $colleague = $this->makeEmployeeRecord([
            'division_id' => $employee->division_id,
            'full_name' => 'Budi Santoso',
        ]);
        AdministrativeRequest::create([
            'employee_id' => $colleague->id,
            'request_type' => 'cuti',
            'category' => 'tahunan',
            'start_date' => '2026-11-24',
            'end_date' => '2026-11-26',
            'day_portion' => 'full',
            'day_count' => 3,
            'reason' => 'Liburan keluarga ke luar kota.',
            'status' => 'approved',
            'submitted_at' => now(),
            'decided_at' => now(),
        ]);

        Sanctum::actingAs($this->makeHrUser());

        $this->getJson('/api/v1/approvals/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data.requests')
            ->assertJsonPath('data.requests.0.id', $id)
            ->assertJsonPath('data.requests.0.employee_name', 'Rina Pratiwi')
            ->assertJsonPath('data.requests.0.conflict_count', 1);
    }

    public function test_hr_approves_supervisor_step_then_hr_step(): void
    {
        [$id] = $this->submittedCuti();
        Sanctum::actingAs($this->makeHrUser());

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'approved',
        ])->assertOk()
            ->assertJsonPath('data.request.status', 'under_review')
            ->assertJsonPath('data.request.approvals.0.status', 'approved')
            ->assertJsonPath('data.request.approvals.1.status', 'pending');

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'approved',
            'note' => 'Disetujui, sisa cuti mencukupi.',
        ])->assertOk()
            ->assertJsonPath('data.request.status', 'approved')
            ->assertJsonPath('data.request.approvals.1.status', 'approved');

        $this->assertDatabaseHas('administrative_requests', [
            'id' => $id,
            'status' => 'approved',
        ]);
    }

    public function test_rejection_requires_a_note_and_skips_remaining_steps(): void
    {
        [$id] = $this->submittedCuti();
        Sanctum::actingAs($this->makeHrUser());

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'rejected',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('note');

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'rejected',
            'note' => 'Bertepatan dengan stok opname bulanan.',
        ])->assertOk()
            ->assertJsonPath('data.request.status', 'rejected')
            ->assertJsonPath('data.request.approvals.1.status', 'skipped');

        $this->assertSame(
            'skipped',
            RequestApproval::query()->where('step_order', 2)->firstOrFail()->status,
        );
    }

    public function test_needs_revision_allows_the_employee_to_resubmit(): void
    {
        [$id, , , $employeeUser] = $this->submittedCuti();
        Sanctum::actingAs($this->makeHrUser());

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'needs_revision',
            'note' => 'Lampirkan surat tugas perjalanan.',
        ])->assertOk()
            ->assertJsonPath('data.request.status', 'needs_revision');

        Sanctum::actingAs($employeeUser);

        $this->postJson('/api/v1/administrative-requests/'.$id.'/resubmit')
            ->assertOk()
            ->assertJsonPath('data.request.status', 'submitted')
            ->assertJsonCount(4, 'data.request.approvals')
            ->assertJsonPath('data.request.approvals.2.round', 2);

        $this->assertDatabaseCount('request_approvals', 4);
    }

    public function test_a_decision_cannot_be_applied_twice(): void
    {
        [$id] = $this->submittedCuti();
        Sanctum::actingAs($this->makeHrUser());

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'approved',
        ])->assertOk();

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'approved',
        ])->assertOk();

        $this->postJson('/api/v1/approvals/requests/'.$id.'/decision', [
            'decision' => 'approved',
        ])->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_employee_cannot_read_the_approval_queue(): void
    {
        Sanctum::actingAs($this->makeEmployeeUser());

        $this->getJson('/api/v1/approvals/requests')->assertForbidden();
    }
}
