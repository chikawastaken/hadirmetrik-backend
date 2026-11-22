<?php

namespace App\Modules\Leave\Application\Support;

use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Leave\Application\Exceptions\RequestNotFound;

final class LeaveAccess
{
    public function __construct(
        private readonly EmployeeDirectory $employees,
    ) {}

    public function employeeFor(int $userId): EmployeeSummary
    {
        $employee = $this->employees->findByUserId($userId);

        if ($employee === null) {
            throw new RequestNotFound('Data karyawan tidak ditemukan.');
        }

        return $employee;
    }

    public function isHr(string $role): bool
    {
        return in_array($role, ['hr', 'admin'], true);
    }
}
