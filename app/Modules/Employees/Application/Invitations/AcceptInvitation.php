<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Application\Exceptions\InvitationAlreadyAccepted;
use App\Modules\Employees\Application\Exceptions\InvitationExpired;
use App\Modules\Employees\Application\Exceptions\InvitationInactive;
use App\Modules\Employees\Application\Exceptions\InvitationNotFound;
use App\Modules\Employees\Application\Exceptions\InvitationNotOwned;
use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;

/**
 * Validates an invitation as an enrollment reservation for the signed-in
 * employee. The `accepted` transition belongs to face enrollment
 * (docs/architecture.md §4.8), so this use case deliberately leaves the
 * invitation pending.
 */
final class AcceptInvitation
{
    public function __construct(
        private readonly InvitationTokenGenerator $tokens,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $plainToken, int $userId): array
    {
        $employee = Employee::query()->where('user_id', $userId)->first();

        if ($employee === null) {
            throw new InvitationNotOwned;
        }

        $invitation = EmployeeInvitation::query()
            ->where('token_hash', $this->tokens->hash($plainToken))
            ->first();

        if ($invitation === null) {
            throw new InvitationNotFound;
        }

        if (! $this->tokens->matches($plainToken, $invitation->token_hash)) {
            throw new InvitationNotFound;
        }

        if ($invitation->employee_id !== $employee->id) {
            throw new InvitationNotOwned;
        }

        if ($invitation->status === 'accepted') {
            throw new InvitationAlreadyAccepted;
        }

        if (! in_array($invitation->status, ['pending', 'sent'], true)) {
            throw new InvitationInactive;
        }

        // The scheduler also expires invitations, but accept must stay
        // race-safe on its own (docs/architecture.md §4.10).
        if ($invitation->expires_at->isPast()) {
            $invitation->update(['status' => 'expired']);

            $this->auditLogger->log(
                entityType: 'invitation',
                entityId: (string) $invitation->id,
                action: 'invitation.expired',
                afterSnapshot: [
                    'employee_id' => $invitation->employee_id,
                    'status' => 'expired',
                ],
                actorUserId: $userId,
            );

            throw new InvitationExpired;
        }

        if ($invitation->purpose !== 'face_enrollment') {
            throw new InvitationInactive;
        }

        return [
            'invitation' => [
                'id' => $invitation->id,
                'purpose' => $invitation->purpose,
                'status' => $invitation->status,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'face_registered_at' => $employee->face_registered_at?->toIso8601String(),
            ],
            'next_step' => 'face_enrollment',
        ];
    }
}
