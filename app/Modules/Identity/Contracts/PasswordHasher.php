<?php

namespace App\Modules\Identity\Contracts;

interface PasswordHasher
{
    public function hash(string $plain): string;

    public function verify(string $plain, string $hash): bool;
}
