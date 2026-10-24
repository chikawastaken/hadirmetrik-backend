<?php

namespace App\Modules\Identity\Application\Login;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Identity\Application\Exceptions\InvalidCredentials;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\Data\AuthResult;
use App\Modules\Identity\Contracts\PasswordHasher;
use App\Modules\Identity\Contracts\TokenIssuer;
use App\Shared\Exceptions\ApiException;

final class LoginUseCase
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly EmployeeDirectory $employees,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
        private readonly SecurityEventRecorder $events,
    ) {}

    public function execute(LoginInput $input): AuthResult
    {
        $identifier = trim($input->identifier);
        $email = $identifier;

        // If identifier does not contain @, resolve employee number to email
        if (! str_contains($identifier, '@')) {
            $employee = $this->employees->findByEmployeeNumber($identifier);
            if ($employee !== null) {
                $email = $employee->workEmail;
            }
        }

        $credentials = $this->accounts->findCredentialsByEmail($email);

        if ($credentials === null || ! $this->hasher->verify($input->password, $credentials->passwordHash)) {
            $this->events->record('login.failed', [
                'identifier' => $identifier,
            ]);

            throw new InvalidCredentials;
        }

        if ($credentials->account->status === 'suspended') {
            throw new \App\Modules\Identity\Application\Exceptions\AccountSuspended;
        }

        $token = $this->tokens->issue($credentials->account, $input->deviceName ?? 'unknown');

        $this->accounts->touchLastLogin($credentials->account->id);

        $this->events->record('login.succeeded', [
            'identifier' => $identifier,
        ], $credentials->account->id);

        return new AuthResult($credentials->account, $token);
    }
}
