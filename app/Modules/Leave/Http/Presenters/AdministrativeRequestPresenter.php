<?php

namespace App\Modules\Leave\Http\Presenters;

use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use App\Modules\Leave\Infrastructure\Models\RequestApproval;

final class AdministrativeRequestPresenter
{
    public function __construct(
        private readonly EmployeeDirectory $employees,
        private readonly UserDirectory $users,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function request(AdministrativeRequest $request, ?int $conflictCount = null): array
    {
        $request->loadMissing('approvals');

        $employee = $this->employees->findById($request->employee_id);
        $approverNames = $this->users->namesByIds(
            $request->approvals->pluck('approver_user_id')->filter()->unique()->values()->all()
        );

        $payload = [
            'id' => $request->id,
            'employee_id' => $request->employee_id,
            'employee_name' => $employee?->name,
            'employee_number' => $employee?->employeeNumber,
            'division_id' => $employee?->divisionId,
            'division' => $employee?->divisionName,
            'request_type' => $request->request_type,
            'category' => $request->category,
            'special_reason' => $request->special_reason,
            'start_date' => $request->start_date?->toDateString(),
            'end_date' => $request->end_date?->toDateString(),
            'day_portion' => $request->day_portion,
            'day_count' => $request->day_count,
            'overtime_date' => $request->overtime_date?->toDateString(),
            'overtime_hours' => $request->overtime_hours,
            'reason' => $request->reason,
            'status' => $request->status,
            'submitted_at' => $request->submitted_at?->toIso8601String(),
            'decided_at' => $request->decided_at?->toIso8601String(),
            'cancelled_at' => $request->cancelled_at?->toIso8601String(),
            'created_at' => $request->created_at?->toIso8601String(),
            'approvals' => $request->approvals
                ->sortBy(fn (RequestApproval $approval): string => sprintf('%03d-%03d', $approval->round, $approval->step_order))
                ->map(fn (RequestApproval $approval): array => [
                    'id' => $approval->id,
                    'round' => $approval->round,
                    'step_order' => $approval->step_order,
                    'approver_role' => $approval->approver_role,
                    'approver_user_id' => $approval->approver_user_id,
                    'approver_name' => $approval->approver_user_id === null
                        ? null
                        : ($approverNames[$approval->approver_user_id] ?? null),
                    'status' => $approval->status,
                    'note' => $approval->note,
                    'decided_at' => $approval->decided_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];

        if ($conflictCount !== null) {
            $payload['conflict_count'] = $conflictCount;
        }

        return $payload;
    }
}
