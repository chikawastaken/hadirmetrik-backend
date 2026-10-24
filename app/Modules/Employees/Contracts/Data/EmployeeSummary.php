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
    ) {}
}
