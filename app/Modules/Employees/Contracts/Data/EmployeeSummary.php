<?php

namespace App\Modules\Employees\Contracts\Data;

final readonly class EmployeeSummary
{
    public function __construct(
        public int $id,
        public string $employeeNumber,
        public string $name,
        public string $workEmail,
        public bool $isActive,
        public ?string $jobTitle = null,
        public ?int $divisionId = null,
        public ?string $divisionName = null,
        public string $employmentStatus = 'active',
        public ?string $faceRegisteredAt = null,
    ) {}
}
