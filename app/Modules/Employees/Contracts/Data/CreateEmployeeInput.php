<?php

namespace App\Modules\Employees\Contracts\Data;

final readonly class CreateEmployeeInput
{
    /**
     * @param  list<string>  $invitationChannels
     */
    public function __construct(
        public string $fullName,
        public string $email,
        public ?string $phone,
        public int $divisionId,
        public string $jobTitle,
        public ?int $managerId,
        public string $employmentStart,
        public string $employmentStatus,
        public int $shiftId,
        public int $workLocationId,
        public float $baseSalary,
        public float $transportAllowance = 0,
        public float $mealAllowance = 0,
        public bool $sendInvitation = true,
        public array $invitationChannels = ['email', 'whatsapp'],
        public int $invitationExpiresInDays = 3,
    ) {}
}
