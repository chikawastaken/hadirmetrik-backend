<?php

namespace App\Modules\Employees\Contracts\Data;

final readonly class CreateEmployeeResult
{
    /**
     * @param  array<string, mixed>  $employeeData
     * @param  array<string, mixed>  $compensationData
     * @param  array<string, mixed>  $shiftAssignmentData
     * @param  array<string, mixed>  $workLocationAssignmentData
     * @param  array<string, mixed>|null  $invitationData
     */
    public function __construct(
        public array $employeeData,
        public array $compensationData,
        public array $shiftAssignmentData,
        public array $workLocationAssignmentData,
        public ?array $invitationData,
        public string $initialPassword,
        public bool $mustChangePassword,
    ) {}
}
