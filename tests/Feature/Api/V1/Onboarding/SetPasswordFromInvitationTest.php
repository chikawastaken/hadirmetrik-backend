<?php

namespace Tests\Feature\Api\V1\Onboarding;

use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class SetPasswordFromInvitationTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_invitation_holder_chooses_a_password_and_receives_a_session(): void
    {
        $user = $this->makeEmployeeUser([
            'email' => 'putri.r@sinarmetrik.co.id',
            'must_change_password' => true,
        ]);
        $employee = $this->makeEmployeeRecord([
            'user_id' => $user->id,
            'employee_number' => 'HM-0251',
        ]);
        [$invitation, $plainToken] = $this->makeInvitation($employee->id, $user->id);

        $response = $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'KaryawanBaru2026',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.must_change_password', false)
            ->assertJsonPath('data.user.email', 'putri.r@sinarmetrik.co.id');

        $this->assertNotEmpty($response->json('data.token'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('KaryawanBaru2026', $user->password));

        // The invitation stays pending: accepting it is a separate step and
        // the `accepted` transition belongs to face enrollment (§4.8).
        $this->assertSame('pending', $invitation->fresh()->status);

        $this->assertDatabaseHas('security_events', [
            'event' => 'password.changed',
            'actor_user_id' => $user->id,
        ]);
    }

    public function test_new_password_works_for_a_normal_login(): void
    {
        $user = $this->makeEmployeeUser([
            'email' => 'putri.r@sinarmetrik.co.id',
            'must_change_password' => true,
        ]);
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id);

        $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'KaryawanBaru2026',
        ])->assertOk();

        $this->postJson('/api/v1/login', [
            'identifier' => 'putri.r@sinarmetrik.co.id',
            'password' => 'KaryawanBaru2026',
        ])->assertOk()->assertJsonPath('data.must_change_password', false);
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = $this->makeEmployeeUser(['must_change_password' => true]);
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id);

        $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'pendek',
            'new_password_confirmation' => 'pendek',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('new_password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_unconfirmed_password_is_rejected(): void
    {
        $user = $this->makeEmployeeUser(['must_change_password' => true]);
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id);

        $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'BedaSekali2026',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('new_password');
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->postJson('/api/v1/invitations/'.str_repeat('a', 32).'/password', [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'KaryawanBaru2026',
        ])->assertStatus(404)
            ->assertJsonPath('errors.code.0', 'INVITATION_NOT_FOUND');
    }

    public function test_expired_invitation_is_rejected_and_marked_expired(): void
    {
        $user = $this->makeEmployeeUser(['must_change_password' => true]);
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [$invitation, $plainToken] = $this->makeInvitation($employee->id, $user->id, [
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'KaryawanBaru2026',
        ])->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'INVITATION_EXPIRED');

        $this->assertSame('expired', $invitation->fresh()->status);
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_revoked_invitation_is_rejected(): void
    {
        $user = $this->makeEmployeeUser(['must_change_password' => true]);
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id, [
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'KaryawanBaru2026',
        ])->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'INVITATION_INACTIVE');
    }

    public function test_accepted_invitation_is_rejected(): void
    {
        $user = $this->makeEmployeeUser(['must_change_password' => true]);
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id, [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'KaryawanBaru2026',
        ])->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'INVITATION_ALREADY_ACCEPTED');
    }

    public function test_account_that_already_changed_its_password_is_rejected(): void
    {
        $user = $this->makeEmployeeUser(['must_change_password' => false]);
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        [, $plainToken] = $this->makeInvitation($employee->id, $user->id);

        $this->postJson("/api/v1/invitations/{$plainToken}/password", [
            'new_password' => 'KaryawanBaru2026',
            'new_password_confirmation' => 'KaryawanBaru2026',
        ])->assertStatus(409)
            ->assertJsonPath('errors.code.0', 'INVITATION_PASSWORD_ALREADY_CHANGED');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
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
