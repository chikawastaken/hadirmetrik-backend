<?php

namespace Tests\Feature\Api\V1\Hr;

use App\Modules\Employees\Infrastructure\Jobs\SendInvitationDelivery;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Modules\Employees\Infrastructure\Models\InvitationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class ResendInvitationTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_hr_can_resend_an_invitation_with_a_rotated_token(): void
    {
        Queue::fake();

        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $employee = $this->makeEmployeeRecord([
            'employee_number' => 'HM-0251',
            'work_email' => 'putri.r@sinarmetrik.co.id',
            'phone_e164' => '+6281227453318',
        ]);

        $previous = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'old-token'),
            'status' => 'sent',
            'requested_channels' => ['email', 'whatsapp'],
            'expires_at' => now()->addDay(),
            'sent_at' => now(),
            'created_by' => $hr->id,
        ]);

        InvitationDelivery::create([
            'invitation_id' => $previous->id,
            'channel' => 'email',
            'recipient' => 'putri.r@sinarmetrik.co.id',
            'status' => 'sent',
            'attempt' => 1,
        ]);

        $response = $this->postJson("/api/v1/hr/employees/{$employee->id}/invitations", [
            'channels' => ['email', 'whatsapp'],
            'expires_in_days' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.previous_invitation_id', $previous->id)
            ->assertJsonPath('data.invitation.status', 'pending')
            ->assertJsonPath('data.invitation.requested_channels', ['email', 'whatsapp'])
            ->assertJsonCount(2, 'data.invitation.deliveries');

        $previous->refresh();
        $this->assertSame('revoked', $previous->status);
        $this->assertNotNull($previous->revoked_at);

        $newInvitation = EmployeeInvitation::query()
            ->whereKey($response->json('data.invitation.id'))
            ->firstOrFail();

        $this->assertSame($employee->id, $newInvitation->employee_id);
        $this->assertNotSame($previous->token_hash, $newInvitation->token_hash);
        $this->assertSame('pending', $newInvitation->status);
        $this->assertNotNull($newInvitation->expires_at);
        $this->assertTrue($newInvitation->expires_at->between(
            now()->addDays(5)->subMinute(),
            now()->addDays(5)->addMinute(),
        ));

        $this->assertDatabaseCount('invitation_deliveries', 3);
        $this->assertDatabaseHas('invitation_deliveries', [
            'invitation_id' => $newInvitation->id,
            'channel' => 'whatsapp',
            'recipient' => '+6281227453318',
            'status' => 'queued',
        ]);

        Queue::assertPushed(SendInvitationDelivery::class, 2);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'invitation',
            'entity_id' => (string) $newInvitation->id,
            'action' => 'invitation.resent',
            'actor_user_id' => $hr->id,
        ]);
    }

    public function test_resend_revokes_every_active_invitation_for_the_employee(): void
    {
        Queue::fake();

        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $employee = $this->makeEmployeeRecord(['work_email' => 'putri.r@sinarmetrik.co.id']);

        $older = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'older-token'),
            'status' => 'pending',
            'requested_channels' => ['email'],
            'expires_at' => now()->addDay(),
            'created_by' => $hr->id,
        ]);

        $newer = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'newer-token'),
            'status' => 'sent',
            'requested_channels' => ['email'],
            'expires_at' => now()->addDay(),
            'sent_at' => now(),
            'created_by' => $hr->id,
        ]);

        $this->postJson("/api/v1/hr/employees/{$employee->id}/invitations")
            ->assertCreated()
            ->assertJsonPath('data.previous_invitation_id', $newer->id);

        $this->assertSame('revoked', $older->fresh()->status);
        $this->assertSame('revoked', $newer->fresh()->status);
    }

    public function test_resend_requires_an_active_invitation(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $employee = $this->makeEmployeeRecord(['work_email' => 'putri.r@sinarmetrik.co.id']);

        $this->postJson("/api/v1/hr/employees/{$employee->id}/invitations")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.code.0', 'INVITATION_NOT_ACTIVE');
    }

    public function test_resend_rejects_whatsapp_without_a_phone_number(): void
    {
        $hr = $this->makeHrUser();
        Sanctum::actingAs($hr);

        $employee = $this->makeEmployeeRecord([
            'work_email' => 'putri.r@sinarmetrik.co.id',
            'phone_e164' => null,
        ]);

        EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'old-token'),
            'status' => 'pending',
            'requested_channels' => ['email'],
            'expires_at' => now()->addDay(),
            'created_by' => $hr->id,
        ]);

        $this->postJson("/api/v1/hr/employees/{$employee->id}/invitations", [
            'channels' => ['whatsapp'],
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'PHONE_REQUIRED');
    }

    public function test_non_hr_cannot_resend_an_invitation(): void
    {
        Sanctum::actingAs($this->makeEmployeeUser());

        $employee = $this->makeEmployeeRecord();

        $this->postJson("/api/v1/hr/employees/{$employee->id}/invitations")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_resend_for_unknown_employee_returns_not_found(): void
    {
        Sanctum::actingAs($this->makeHrUser());

        $this->postJson('/api/v1/hr/employees/999999/invitations')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
