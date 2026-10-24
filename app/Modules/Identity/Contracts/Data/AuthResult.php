<?php

namespace App\Modules\Identity\Contracts\Data;

final readonly class AuthResult
{
    public function __construct(
        public Account $account,
        public IssuedToken $token,
    ) {}
}
