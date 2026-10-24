<?php

namespace App\Modules\Identity\Application\Login;

final readonly class LoginInput
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null,
    ) {}
}
