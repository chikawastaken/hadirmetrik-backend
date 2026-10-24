<?php

namespace App\Modules\Identity\Application\RegisterEmployee;

final readonly class RegisterEmployeeInput
{
    public function __construct(
        public string $employeeNumber,
        public string $workEmail,
        public string $password,
        public string $invitationCode,
    ) {}
}
