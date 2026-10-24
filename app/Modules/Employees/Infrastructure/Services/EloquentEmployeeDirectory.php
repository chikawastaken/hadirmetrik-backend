<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Employees\Infrastructure\Models\Employee;

final class EloquentEmployeeDirectory implements EmployeeDirectory
{
    public function findByWorkEmail(string $workEmail): ?EmployeeSummary
    {
        $employee = Employee::query()->where('work_email', $workEmail)->first();

        if ($employee === null) {
            return null;
        }

        return new EmployeeSummary(
            $employee->id,
            $employee->employee_number,
            $employee->name,
            $employee->work_email,
            (bool) $employee->is_active,
        );
    }
}
