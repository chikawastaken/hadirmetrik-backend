<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Application\Exceptions\InvitationNotRevocable;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;

/**
 * Revokes an invitation that is still pending/sent. Revoking twice is a
 * no-op from the state machine's point of view and therefore rejected
 * (docs/architecture.md §4.9, §5.6).
 */
final class RevokeInvitation
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $invitationId, ?int $actorUserId): array
    {
        $invitation = EmployeeInvitation::query()->findOrFail($invitationId);

        if (! in_array($invitation->status, ['pending', 'sent'], true)) {
            throw new InvitationNotRevocable;
        }

        $previousStatus = $invitation->status;
        $revokedAt = now();

        $invitation->update([
            'status' => 'revoked',
            'revoked_at' => $revokedAt,
        ]);

        $this->auditLogger->log(
            entityType: 'invitation',
            entityId: (string) $invitation->id,
            action: 'invitation.revoked',
            beforeSnapshot: ['status' => $previousStatus],
            afterSnapshot: [
                'status' => 'revoked',
                'employee_id' => $invitation->employee_id,
            ],
            actorUserId: $actorUserId,
        );

        return [
            'invitation_id' => $invitation->id,
            'employee_id' => $invitation->employee_id,
            'status' => $invitation->status,
            'revoked_at' => $revokedAt->toIso8601String(),
        ];
    }
}
