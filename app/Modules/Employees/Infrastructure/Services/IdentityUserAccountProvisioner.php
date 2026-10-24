<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\Data\UserAccountData;
use App\Modules\Employees\Contracts\UserAccountProvisioner;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\PasswordHasher;

final class IdentityUserAccountProvisioner implements UserAccountProvisioner
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
    ) {}

    public function provision(
        string $name,
        string $email,
        string $plainPassword,
        string $role = 'employee',
        string $status = 'active',
        bool $mustChangePassword = true,
    ): UserAccountData {
        $hash = $this->hasher->hash($plainPassword);
        $account = $this->accounts->create(
            $name,
            $email,
            $hash,
            $role,
            $status,
            $mustChangePassword,
        );

        return new UserAccountData(
            $account->id,
            $account->name,
            $account->email,
            $account->role,
            $account->status,
            $account->mustChangePassword,
        );
    }
}
