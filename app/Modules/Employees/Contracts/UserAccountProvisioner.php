<?php

namespace App\Modules\Employees\Contracts;

use App\Modules\Employees\Contracts\Data\UserAccountData;

interface UserAccountProvisioner
{
    public function provision(
        string $name,
        string $email,
        string $plainPassword,
        string $role = 'employee',
        string $status = 'active',
        bool $mustChangePassword = true,
    ): UserAccountData;

    public function resetPassword(int $userId, string $plainPassword): void;
}
