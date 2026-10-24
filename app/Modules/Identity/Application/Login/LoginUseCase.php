<?php

namespace App\Modules\Identity\Application\Login;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Identity\Application\Exceptions\InvalidCredentials;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\Data\AuthResult;
use App\Modules\Identity\Contracts\PasswordHasher;
use App\Modules\Identity\Contracts\TokenIssuer;

final class LoginUseCase
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
        private readonly SecurityEventRecorder $events,
    ) {}

    public function execute(LoginInput $input): AuthResult
    {
        $credentials = $this->accounts->findCredentialsByEmail($input->email);

        if ($credentials === null || ! $this->hasher->verify($input->password, $credentials->passwordHash)) {
            $this->events->record('login.failed', ['email' => $input->email]);

            throw new InvalidCredentials;
        }

        $token = $this->tokens->issue($credentials->account, $input->deviceName ?? 'unknown');

        $this->events->record('login.succeeded', [
            'email' => $input->email,
        ], $credentials->account->id);

        return new AuthResult($credentials->account, $token);
    }
}
