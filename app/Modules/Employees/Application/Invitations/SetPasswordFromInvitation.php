<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Employees\Application\Exceptions\InvitationNotFound;
use App\Modules\Employees\Application\Exceptions\InvitationPasswordAlreadyChanged;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\Data\AuthResult;
use App\Modules\Identity\Contracts\PasswordHasher;
use App\Modules\Identity\Contracts\TokenIssuer;

/**
 * Lets the holder of an invitation link choose their own password and walk
 * away with a session, so HR never has to deliver the temporary password.
 * The link is the credential: it is single-purpose, expiring, and stored
 * hashed (docs/architecture.md §4.8).
 */
final class SetPasswordFromInvitation
{
    public function __construct(
        private readonly ResolveInvitation $invitations,
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
        private readonly SecurityEventRecorder $events,
    ) {}

    public function execute(string $plainToken, string $newPassword, string $deviceName): AuthResult
    {
        $invitation = $this->invitations->lookup($plainToken);
        $this->invitations->assertUsable($invitation);

        $employee = Employee::query()->find($invitation->employee_id);

        if ($employee === null || $employee->user_id === null) {
            throw new InvitationNotFound;
        }

        $credentials = $this->accounts->findCredentialsById((int) $employee->user_id);

        if ($credentials === null) {
            throw new InvitationNotFound;
        }

        // The temporary password is gone: the employee must sign in normally.
        if (! $credentials->account->mustChangePassword) {
            throw new InvitationPasswordAlreadyChanged;
        }

        $this->accounts->updatePassword(
            $credentials->account->id,
            $this->hasher->hash($newPassword),
            false,
        );

        $account = $this->accounts->findById($credentials->account->id);

        if ($account === null) {
            throw new InvitationNotFound;
        }

        $token = $this->tokens->issue($account, $deviceName);

        $this->events->record('password.changed', [
            'via' => 'invitation',
        ], $account->id);

        return new AuthResult($account, $token);
    }
}
