<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Employees\Infrastructure\Models\Employee;

final class EloquentEmployeeDirectory implements EmployeeDirectory
{
    public function findByEmployeeNumber(string $employeeNumber): ?EmployeeSummary
    {
        $employee = Employee::query()->where('employee_number', $employeeNumber)->first();

        if ($employee === null) {
            return null;
        }

        return new EmployeeSummary(
            $employee->id,
            $employee->employee_number,
            $employee->full_name,
            $employee->work_email,
            $employee->employment_status !== 'terminated',
        );
    }
}
