<?php

namespace App\Modules\Employees\Application\ListEmployees;

use App\Modules\Employees\Infrastructure\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

final class ListEmployeesUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(?string $search = null, ?int $divisionId = null, ?string $status = null): array
    {
        $query = Employee::query()
            ->with(['division', 'manager'])
            ->latest('id');

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhere('work_email', 'like', "%{$search}%");
            });
        }

        if ($divisionId !== null) {
            $query->where('division_id', $divisionId);
        }

        if ($status !== null && $status !== '') {
            $query->where('employment_status', $status);
        }

        $employees = $query->get();

        return [
            'employees' => $employees->map(fn (Employee $e): array => [
                'id' => $e->id,
                'employee_number' => $e->employee_number,
                'full_name' => $e->full_name,
                'work_email' => $e->work_email,
                'phone_e164' => $e->phone_e164,
                'division' => $e->division?->name,
                'division_id' => $e->division_id,
                'job_title' => $e->job_title,
                'manager' => $e->manager?->full_name,
                'manager_id' => $e->manager_id,
                'employment_status' => $e->employment_status,
                'employment_start' => $e->employment_start?->format('Y-m-d'),
                'face_registered_at' => $e->face_registered_at?->toIso8601String(),
                'has_face' => $e->face_registered_at !== null,
            ])->values()->all(),
        ];
    }
}
