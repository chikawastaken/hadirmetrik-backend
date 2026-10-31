<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Employees\Application\Exceptions\InvitationNotOwned;
use App\Modules\Employees\Infrastructure\Models\Employee;

/**
 * Validates an invitation as an enrollment reservation for the signed-in
 * employee. The `accepted` transition belongs to face enrollment
 * (docs/architecture.md §4.8), so this use case deliberately leaves the
 * invitation pending.
 */
final class AcceptInvitation
{
    public function __construct(
        private readonly ResolveInvitation $invitations,
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

        $invitation = $this->invitations->lookup($plainToken);

        if ($invitation->employee_id !== $employee->id) {
            throw new InvitationNotOwned;
        }

        $this->invitations->assertUsable($invitation, $userId);

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
