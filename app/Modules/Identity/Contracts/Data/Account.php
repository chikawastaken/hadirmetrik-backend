<?php

namespace App\Modules\Identity\Contracts\Data;

final readonly class Account
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
        public string $status = 'active',
        public bool $mustChangePassword = true,
    ) {}
}
