<?php

namespace App\Modules\Identity\Application\ChangePassword;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Identity\Application\Exceptions\InvalidCredentials;
use App\Modules\Identity\Application\Exceptions\InvalidCurrentPassword;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\PasswordHasher;

final class ChangePasswordUseCase
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly SecurityEventRecorder $events,
    ) {}

    public function execute(ChangePasswordInput $input): void
    {
        $credentials = $this->accounts->findCredentialsById($input->accountId);

        if ($credentials === null) {
            throw new InvalidCredentials('Sesi tidak valid.');
        }

        if (! $this->hasher->verify($input->currentPassword, $credentials->passwordHash)) {
            $this->events->record('password.change_failed', [
                'reason' => 'invalid_current_password',
            ], $input->accountId);

            throw new InvalidCurrentPassword;
        }

        $this->accounts->updatePassword(
            $input->accountId,
            $this->hasher->hash($input->newPassword),
            false,
        );

        $this->events->record('password.changed', [], $input->accountId);
    }
}
