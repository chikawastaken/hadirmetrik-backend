<?php

namespace Tests\Feature\Api\V1\Hr;

use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class RevokeInvitationTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_hr_can_revoke_a_pending_invitation(): void
    {
        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $employee = $this->makeEmployeeRecord(['work_email' => 'putri.r@sinarmetrik.co.id']);
        $invitation = $this->makeInvitation($employee->id, $hr->id, 'pending');

        $this->deleteJson("/api/v1/hr/invitations/{$invitation->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.invitation_id', $invitation->id)
            ->assertJsonPath('data.status', 'revoked');

        $invitation->refresh();
        $this->assertSame('revoked', $invitation->status);
        $this->assertNotNull($invitation->revoked_at);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'invitation',
            'entity_id' => (string) $invitation->id,
            'action' => 'invitation.revoked',
            'actor_user_id' => $hr->id,
        ]);
    }

    public function test_revoking_twice_is_rejected(): void
    {
        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $employee = $this->makeEmployeeRecord();
        $invitation = $this->makeInvitation($employee->id, $hr->id, 'revoked');

        $this->deleteJson("/api/v1/hr/invitations/{$invitation->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.code.0', 'INVITATION_NOT_REVOCABLE');
    }

    public function test_accepted_invitation_cannot_be_revoked(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $employee = $this->makeEmployeeRecord();
        $invitation = $this->makeInvitation($employee->id, null, 'accepted', [
            'accepted_at' => now(),
        ]);

        $this->deleteJson("/api/v1/hr/invitations/{$invitation->id}")
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'INVITATION_NOT_REVOCABLE');
    }

    public function test_non_hr_cannot_revoke_an_invitation(): void
    {
        Sanctum::actingAs($this->makeEmployeeUser());

        $employee = $this->makeEmployeeRecord();
        $invitation = $this->makeInvitation($employee->id, null, 'pending');

        $this->deleteJson("/api/v1/hr/invitations/{$invitation->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_revoke_for_unknown_invitation_returns_not_found(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $this->deleteJson('/api/v1/hr/invitations/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeInvitation(
        int $employeeId,
        ?int $createdBy,
        string $status,
        array $attributes = [],
    ): EmployeeInvitation {
        return EmployeeInvitation::create(array_merge([
            'employee_id' => $employeeId,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'token-'.$status),
            'status' => $status,
            'requested_channels' => ['email'],
            'expires_at' => now()->addDays(3),
            'created_by' => $createdBy ?? $this->makeHrUser()->id,
        ], $attributes));
    }
}
