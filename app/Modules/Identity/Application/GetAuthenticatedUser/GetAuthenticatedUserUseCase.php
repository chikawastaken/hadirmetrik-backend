<?php

namespace App\Modules\Identity\Application\GetAuthenticatedUser;

use App\Modules\Identity\Application\Exceptions\InvalidCredentials;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\Data\Account;

final class GetAuthenticatedUserUseCase
{
    public function __construct(private readonly AccountRepository $accounts) {}

    public function execute(int $accountId): Account
    {
        $account = $this->accounts->findById($accountId);

        if ($account === null) {
            throw new InvalidCredentials('Sesi tidak valid.');
        }

        return $account;
    }
}
