<?php

namespace App\Modules\Employees\Contracts;

use App\Modules\Employees\Contracts\Data\EmployeeSummary;

/**
 * Published contract: read access to HR employee records.
 */
interface EmployeeDirectory
{
    public function findByWorkEmail(string $workEmail): ?EmployeeSummary;

    public function findByEmployeeNumber(string $employeeNumber): ?EmployeeSummary;
}
