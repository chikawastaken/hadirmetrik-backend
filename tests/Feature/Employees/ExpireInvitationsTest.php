<?php

namespace Tests\Feature\Employees;

use App\Modules\Audit\Infrastructure\Models\AuditLog;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class ExpireInvitationsTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_expiry_command_expires_only_overdue_pending_and_sent_invitations(): void
    {
        $hr = $this->makeHrUser();
        $employee = $this->makeEmployeeRecord();

        $overduePending = $this->makeInvitation($employee->id, $hr->id, 'pending', now()->subMinute());
        $overdueSent = $this->makeInvitation($employee->id, $hr->id, 'sent', now()->subDay());
        $future = $this->makeInvitation($employee->id, $hr->id, 'pending', now()->addDay());
        $alreadyRevoked = $this->makeInvitation($employee->id, $hr->id, 'revoked', now()->subDay());
        $alreadyExpired = $this->makeInvitation($employee->id, $hr->id, 'expired', now()->subDay());

        $this->artisan('invitations:expire')
            ->expectsOutputToContain('2 undangan')
            ->assertExitCode(0);

        $this->assertSame('expired', $overduePending->fresh()->status);
        $this->assertSame('expired', $overdueSent->fresh()->status);
        $this->assertSame('pending', $future->fresh()->status);
        $this->assertSame('revoked', $alreadyRevoked->fresh()->status);
        $this->assertSame('expired', $alreadyExpired->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'invitation',
            'entity_id' => (string) $overduePending->id,
            'action' => 'invitation.expired',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'invitation',
            'entity_id' => (string) $overdueSent->id,
            'action' => 'invitation.expired',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'entity_id' => (string) $future->id,
            'action' => 'invitation.expired',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'entity_id' => (string) $alreadyRevoked->id,
            'action' => 'invitation.expired',
        ]);
    }

    public function test_expiry_command_is_idempotent(): void
    {
        $hr = $this->makeHrUser();
        $employee = $this->makeEmployeeRecord();
        $invitation = $this->makeInvitation($employee->id, $hr->id, 'pending', now()->subHour());

        $this->artisan('invitations:expire')->assertExitCode(0);
        $this->artisan('invitations:expire')->expectsOutputToContain('0 undangan')->assertExitCode(0);

        $this->assertSame('expired', $invitation->fresh()->status);
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('entity_type', 'invitation')
                ->where('entity_id', (string) $invitation->id)
                ->where('action', 'invitation.expired')
                ->count(),
        );
    }

    public function test_expiry_is_scheduled_every_ten_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains((string) $event->command, 'invitations:expire'));

        $this->assertNotNull($event, 'invitations:expire is not scheduled.');
        $this->assertSame('*/10 * * * *', $event->expression);
    }

    private function makeInvitation(
        int $employeeId,
        int $createdBy,
        string $status,
        \DateTimeInterface $expiresAt,
    ): EmployeeInvitation {
        return EmployeeInvitation::create([
            'employee_id' => $employeeId,
            'purpose' => 'face_enrollment',
            'token_hash' => hash('sha256', 'token-'.uniqid('', true)),
            'status' => $status,
            'requested_channels' => ['email'],
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);
    }
}
