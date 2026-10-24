<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\EmployeeNumberGenerator;
use App\Modules\Employees\Infrastructure\Models\Employee;
use Illuminate\Support\Facades\DB;

final class DatabaseEmployeeNumberGenerator implements EmployeeNumberGenerator
{
    public function next(): string
    {
        // Find highest existing HM-#### suffix
        $highest = Employee::query()
            ->select('employee_number')
            ->where('employee_number', 'like', 'HM-%')
            ->orderBy('id', 'desc')
            ->value('employee_number');

        if ($highest && preg_match('/^HM-(\d+)$/', $highest, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $count = Employee::query()->count();
            $nextNumber = $count + 1;
        }

        return sprintf('HM-%04d', $nextNumber);
    }
}
