<?php

namespace Tests\Feature\Api\V1\Onboarding;

use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class AcceptInvitationTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_employee_can_accept_their_own_valid_invitation(): void
    {
        $user = $this->makeEmployeeUser(['email' => 'putri.r@sinarmetrik.co.id']);
        Sanctum::actingAs($user);

        $employee = $this->makeEmployeeRecord([
            'user_id' => $user->id,
            'employee_number' => 'HM-0251',
            'full_name' => 'Putri Ramadhani',
            'work_email' => 'putri.r@sinarmetrik.co.id',
        ]);

        [$invitation, $plainToken] = $this->makeInvitation($employee->id, $user->id);

        $this->postJson("/api/v1/invitations/{$plainToken}/accept")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.invitation.id', $invitation->id)
            ->assertJsonPath('data.invitation.purpose', 'face_enrollment')
            ->assertJsonPath('data.employee.id', $employee->id)
            ->assertJsonPath('data.employee.employee_number', 'HM-0251')
            ->assertJsonPath('data.next_step', 'face_enrollment');

        // Accept is a reservation: the status transition happens on face
        // enrollment (docs/architecture.md §4.8), not here.
        $this->assertSame('pending', $invitation->fresh()->status);
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_expired_invitation_is_rejected_and_marked_expired(): void
    {
        $user = $this->makeEmployeeUser();
        Sanctum::actingAs($user);

        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [$invitation, $plainToken] = $this->makeInvitation($employee->id, $user->id, [
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson("/api/v1/invitations/{$plainToken}/accept")
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'INVITATION_EXPIRED');

        $invitation->refresh();
        $this->assertSame('expired', $invitation->status);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'invitation',
            'entity_id' => (string) $invitation->id,
            'action' => 'invitation.expired',
        ]);
    }

    public function test_revoked_invitation_is_rejected(): void
    {
        $user = $this->makeEmployeeUser();
        Sanctum::actingAs($user);

        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id, [
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $this->postJson("/api/v1/invitations/{$plainToken}/accept")
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'INVITATION_INACTIVE');
    }

    public function test_already_accepted_invitation_is_rejected(): void
    {
        $user = $this->makeEmployeeUser();
        Sanctum::actingAs($user);

        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id, [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $this->postJson("/api/v1/invitations/{$plainToken}/accept")
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'INVITATION_ALREADY_ACCEPTED');
    }

    public function test_employee_cannot_accept_another_employees_invitation(): void
    {
        $user = $this->makeEmployeeUser();
        Sanctum::actingAs($user);

        $ownEmployee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $otherEmployee = $this->makeEmployeeRecord();

        [, $plainToken] = $this->makeInvitation($otherEmployee->id, $otherEmployee->user_id);

        $this->postJson("/api/v1/invitations/{$plainToken}/accept")
            ->assertStatus(403)
            ->assertJsonPath('errors.code.0', 'INVITATION_NOT_OWNED');
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $user = $this->makeEmployeeUser();
        Sanctum::actingAs($user);
        $this->makeEmployeeRecord(['user_id' => $user->id]);

        $this->postJson('/api/v1/invitations/'.str_repeat('a', 32).'/accept')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_guest_cannot_accept_an_invitation(): void
    {
        $this->postJson('/api/v1/invitations/'.str_repeat('b', 32).'/accept')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_user_without_employee_profile_cannot_accept(): void
    {
        $user = $this->makeHrUser();
        Sanctum::actingAs($user);

        $other = $this->makeEmployeeRecord();
        [, $plainToken] = $this->makeInvitation($other->id, $other->user_id);

        $this->postJson("/api/v1/invitations/{$plainToken}/accept")
            ->assertStatus(403)
            ->assertJsonPath('errors.code.0', 'INVITATION_NOT_OWNED');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: EmployeeInvitation, 1: string}
     */
    private function makeInvitation(int $employeeId, ?int $createdBy, array $attributes = []): array
    {
        $tokenPair = app(InvitationTokenGenerator::class)->generate();

        $invitation = EmployeeInvitation::create(array_merge([
            'employee_id' => $employeeId,
            'purpose' => 'face_enrollment',
            'token_hash' => $tokenPair['token_hash'],
            'status' => 'pending',
            'requested_channels' => ['email'],
            'expires_at' => now()->addDays(3),
            'created_by' => $createdBy ?? $this->makeHrUser()->id,
        ], $attributes));

        return [$invitation, $tokenPair['plain_token']];
    }
}
