<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;

/**
 * Expires overdue invitations in bulk. Encoding the guard in the UPDATE keeps
 * the job idempotent even when two schedulers overlap
 * (docs/architecture.md §4.10).
 */
final class ExpireInvitations
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(): int
    {
        $expiredCount = 0;

        EmployeeInvitation::query()
            ->whereIn('status', ['pending', 'sent'])
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->lazyById(200)
            ->each(function (EmployeeInvitation $invitation) use (&$expiredCount): void {
                $updated = EmployeeInvitation::query()
                    ->whereKey($invitation->id)
                    ->whereIn('status', ['pending', 'sent'])
                    ->where('expires_at', '<', now())
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now(),
                    ]);

                if ($updated === 0) {
                    return;
                }

                $expiredCount++;

                $this->auditLogger->log(
                    entityType: 'invitation',
                    entityId: (string) $invitation->id,
                    action: 'invitation.expired',
                    beforeSnapshot: ['status' => $invitation->status],
                    afterSnapshot: [
                        'employee_id' => $invitation->employee_id,
                        'status' => 'expired',
                    ],
                );
            });

        return $expiredCount;
    }
}
