<?php

namespace App\Modules\Employees\Contracts\Data;

final readonly class UserAccountData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
        public string $status,
        public bool $mustChangePassword,
    ) {}
}
