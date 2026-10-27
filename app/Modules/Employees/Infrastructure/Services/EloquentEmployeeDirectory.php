<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Employees\Infrastructure\Models\Employee;

final class EloquentEmployeeDirectory implements EmployeeDirectory
{
    public function findByEmployeeNumber(string $employeeNumber): ?EmployeeSummary
    {
        $employee = Employee::query()
            ->with('division')
            ->where('employee_number', $employeeNumber)
            ->first();

        return $employee === null ? null : $this->toSummary($employee);
    }

    public function findByUserId(int $userId): ?EmployeeSummary
    {
        $employee = Employee::query()
            ->with('division')
            ->where('user_id', $userId)
            ->first();

        return $employee === null ? null : $this->toSummary($employee);
    }

    private function toSummary(Employee $employee): EmployeeSummary
    {
        return new EmployeeSummary(
            $employee->id,
            $employee->employee_number,
            $employee->full_name,
            $employee->work_email,
            $employee->employment_status !== 'terminated',
            $employee->job_title,
            $employee->division_id,
            $employee->division?->name,
            $employee->employment_status,
            $employee->face_registered_at?->toIso8601String(),
        );
    }
}
