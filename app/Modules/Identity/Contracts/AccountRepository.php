<?php

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\Contracts\Data\Account;
use App\Modules\Identity\Contracts\Data\AccountCredentials;

/**
 * Persistence port for user accounts. Implemented over Eloquent, consumed by
 * the identity use cases so they stay independent of the database.
 */
interface AccountRepository
{
    public function findById(int $id): ?Account;

    public function findCredentialsByEmail(string $email): ?AccountCredentials;

    public function existsByEmail(string $email): bool;

    public function create(string $name, string $email, string $passwordHash, string $role): Account;
}
