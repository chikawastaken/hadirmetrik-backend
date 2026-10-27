<?php

namespace App\Modules\Employees\Application\GetEmployeeDetail;

use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\EmployeeCompensation;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Modules\Employees\Infrastructure\Models\EmployeeShiftAssignment;
use App\Modules\Employees\Infrastructure\Models\EmployeeWorkLocationAssignment;

final class GetEmployeeDetailUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(int $employeeId): array
    {
        $employee = Employee::query()
            ->with(['division', 'manager'])
            ->findOrFail($employeeId);

        return [
            'employee' => $this->employeeData($employee),
            'compensation' => $this->activeCompensation($employee->id),
            'shift_assignment' => $this->activeShiftAssignment($employee->id),
            'work_location_assignment' => $this->activeWorkLocationAssignment($employee->id),
            'invitation' => $this->latestInvitation($employee->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeData(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->full_name,
            'work_email' => $employee->work_email,
            'phone_e164' => $employee->phone_e164,
            'division' => $employee->division === null ? null : [
                'id' => $employee->division->id,
                'name' => $employee->division->name,
            ],
            'job_title' => $employee->job_title,
            'manager' => $employee->manager === null ? null : [
                'id' => $employee->manager->id,
                'full_name' => $employee->manager->full_name,
                'employee_number' => $employee->manager->employee_number,
            ],
            'employment_start' => $employee->employment_start?->format('Y-m-d'),
            'employment_status' => $employee->employment_status,
            'face_registered_at' => $employee->face_registered_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private function activeCompensation(int $employeeId): ?array
    {
        $compensation = EmployeeCompensation::query()
            ->where('employee_id', $employeeId)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($compensation === null) {
            return null;
        }

        $totalMonthly = (float) $compensation->base_salary
            + (float) $compensation->transport_allowance
            + (float) $compensation->meal_allowance;

        return [
            'base_salary' => number_format((float) $compensation->base_salary, 2, '.', ''),
            'transport_allowance' => number_format((float) $compensation->transport_allowance, 2, '.', ''),
            'meal_allowance' => number_format((float) $compensation->meal_allowance, 2, '.', ''),
            'total_monthly' => number_format($totalMonthly, 2, '.', ''),
            'currency' => $compensation->currency,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function activeShiftAssignment(int $employeeId): ?array
    {
        $assignment = EmployeeShiftAssignment::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($assignment === null) {
            return null;
        }

        return [
            'shift_id' => $assignment->shift_id,
            'name' => $assignment->shift->name,
            'is_overnight' => $assignment->shift->is_overnight,
            'effective_from' => $assignment->effective_from->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function activeWorkLocationAssignment(int $employeeId): ?array
    {
        $assignment = EmployeeWorkLocationAssignment::query()
            ->with('workLocation')
            ->where('employee_id', $employeeId)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($assignment === null) {
            return null;
        }

        return [
            'work_location_id' => $assignment->work_location_id,
            'name' => $assignment->workLocation->name,
            'effective_from' => $assignment->effective_from->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestInvitation(int $employeeId): ?array
    {
        $invitation = EmployeeInvitation::query()
            ->with(['deliveries' => fn ($query) => $query->orderBy('id')])
            ->where('employee_id', $employeeId)
            ->latest('id')
            ->first();

        if ($invitation === null) {
            return null;
        }

        return [
            'id' => $invitation->id,
            'status' => $invitation->status,
            'requested_channels' => $invitation->requested_channels,
            'expires_at' => $invitation->expires_at->toIso8601String(),
            'deliveries' => $invitation->deliveries
                ->map(fn ($delivery): array => [
                    'channel' => $delivery->channel,
                    'recipient' => $delivery->recipient,
                    'status' => $delivery->status,
                ])
                ->all(),
        ];
    }
}
