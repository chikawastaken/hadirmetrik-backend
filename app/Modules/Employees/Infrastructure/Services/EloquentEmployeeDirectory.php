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
            ->with(['division', 'manager'])
            ->where('employee_number', $employeeNumber)
            ->first();

        return $employee === null ? null : $this->toSummary($employee);
    }

    public function findByUserId(int $userId): ?EmployeeSummary
    {
        $employee = Employee::query()
            ->with(['division', 'manager'])
            ->where('user_id', $userId)
            ->first();

        return $employee === null ? null : $this->toSummary($employee);
    }

    public function findById(int $id): ?EmployeeSummary
    {
        $employee = Employee::query()
            ->with(['division', 'manager'])
            ->find($id);

        return $employee === null ? null : $this->toSummary($employee);
    }

    public function findActiveByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Employee::query()
            ->with(['division', 'manager'])
            ->whereIn('id', $ids)
            ->where('employment_status', '!=', 'terminated')
            ->get()
            ->map(fn (Employee $employee): EmployeeSummary => $this->toSummary($employee))
            ->all();
    }

    public function activeEmployeeIdsInDivision(int $divisionId): array
    {
        return Employee::query()
            ->where('division_id', $divisionId)
            ->where('employment_status', '!=', 'terminated')
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();
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
            $employee->manager?->user_id,
        );
    }
}
