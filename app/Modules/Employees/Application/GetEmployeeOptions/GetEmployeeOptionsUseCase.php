<?php

namespace App\Modules\Employees\Application\GetEmployeeOptions;

use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\Shift;
use App\Modules\Employees\Infrastructure\Models\WorkLocation;

final class GetEmployeeOptionsUseCase
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function execute(): array
    {
        return [
            'divisions' => Division::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Division $division): array => [
                    'id' => $division->id,
                    'name' => $division->name,
                    'code' => $division->code,
                ])
                ->all(),
            'shifts' => Shift::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Shift $shift): array => [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'start_time' => substr((string) $shift->start_time, 0, 5),
                    'end_time' => substr((string) $shift->end_time, 0, 5),
                    'timezone' => $shift->timezone,
                    'is_overnight' => $shift->is_overnight,
                ])
                ->all(),
            'work_locations' => WorkLocation::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (WorkLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'geofence_radius_m' => $location->geofence_radius_m,
                    'timezone' => $location->timezone,
                ])
                ->all(),
            'managers' => Employee::query()
                ->where('employment_status', '!=', 'terminated')
                ->orderBy('full_name')
                ->get(['id', 'employee_number', 'full_name', 'job_title'])
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'full_name' => $employee->full_name,
                    'job_title' => $employee->job_title,
                ])
                ->all(),
        ];
    }
}
