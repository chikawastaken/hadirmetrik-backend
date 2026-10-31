<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Employees\Application\Exceptions\InvitationNotActive;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Shared\Contracts\TransactionManager;

/**
 * Rotates the active invitation: every active row is revoked and a fresh
 * token is issued in the same transaction (docs/architecture.md §4.9, §5.6).
 */
final class ResendInvitation
{
    public function __construct(
        private readonly IssueInvitation $issueInvitation,
        private readonly TransactionManager $transaction,
    ) {}

    /**
     * @param  list<string>|null  $channels
     * @return array<string, mixed>
     */
    public function execute(
        int $employeeId,
        ?array $channels,
        ?int $expiresInDays,
        ?int $actorUserId,
    ): array {
        $employee = Employee::query()->findOrFail($employeeId);

        $active = EmployeeInvitation::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'sent'])
            ->latest('id')
            ->get();

        if ($active->isEmpty()) {
            throw new InvitationNotActive;
        }

        $previous = $active->first();

        return $this->transaction->run(function () use ($employee, $active, $previous, $channels, $expiresInDays, $actorUserId): array {
            // Revoke every still-active row, not only the latest one: the
            // single-active-invitation constraint is not enforced yet.
            EmployeeInvitation::query()
                ->whereKey($active->modelKeys())
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                ]);

            $invitation = $this->issueInvitation->execute(
                employee: $employee,
                channels: $channels ?? $previous->requested_channels,
                expiresInDays: $expiresInDays ?? (int) config('invitations.default_expiry_days'),
                actorUserId: $actorUserId,
                auditAction: 'invitation.resent',
                auditContext: ['previous_invitation_id' => $previous->id],
            );

            return [
                'employee_id' => $employee->id,
                'previous_invitation_id' => $previous->id,
                'invitation' => $invitation,
            ];
        });
    }
}
