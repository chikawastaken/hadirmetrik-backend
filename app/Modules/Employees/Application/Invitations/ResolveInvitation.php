<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Application\Exceptions\InvitationAlreadyAccepted;
use App\Modules\Employees\Application\Exceptions\InvitationExpired;
use App\Modules\Employees\Application\Exceptions\InvitationInactive;
use App\Modules\Employees\Application\Exceptions\InvitationNotFound;
use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;

/**
 * Shared token validation for every endpoint that receives an invitation link.
 * The scheduler also expires invitations, but each endpoint must stay
 * race-safe on its own (docs/architecture.md §4.10).
 */
final class ResolveInvitation
{
    public function __construct(
        private readonly InvitationTokenGenerator $tokens,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function lookup(string $plainToken): EmployeeInvitation
    {
        $invitation = EmployeeInvitation::query()
            ->where('token_hash', $this->tokens->hash($plainToken))
            ->first();

        if ($invitation === null || ! $this->tokens->matches($plainToken, $invitation->token_hash)) {
            throw new InvitationNotFound;
        }

        return $invitation;
    }

    public function assertUsable(EmployeeInvitation $invitation, ?int $actorUserId = null): void
    {
        if ($invitation->status === 'accepted') {
            throw new InvitationAlreadyAccepted;
        }

        if (! in_array($invitation->status, ['pending', 'sent'], true)) {
            throw new InvitationInactive;
        }

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
                actorUserId: $actorUserId,
            );

            throw new InvitationExpired;
        }

        if ($invitation->purpose !== 'face_enrollment') {
            throw new InvitationInactive;
        }
    }
}
