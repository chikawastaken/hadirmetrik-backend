<?php

namespace App\Modules\Identity\Application\ChangePassword;

final readonly class ChangePasswordInput
{
    public function __construct(
        public int $accountId,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
