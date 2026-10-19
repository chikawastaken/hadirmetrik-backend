<?php

namespace App\Modules\Identity\Contracts\Data;

final readonly class AccountCredentials
{
    public function __construct(
        public Account $account,
        public string $passwordHash,
    ) {}
}
