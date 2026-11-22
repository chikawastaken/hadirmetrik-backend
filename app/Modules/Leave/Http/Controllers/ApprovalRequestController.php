<?php

namespace App\Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Leave\Application\DecideRequest\DecideAdministrativeRequest;
use App\Modules\Leave\Http\Presenters\AdministrativeRequestPresenter;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use App\Modules\Leave\Infrastructure\Models\RequestApproval;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class ApprovalRequestController extends Controller
{
    public function __construct(
        private readonly EmployeeDirectory $employees,
        private readonly AdministrativeRequestPresenter $presenter,
        private readonly DecideAdministrativeRequest $decider,
    ) {}

    public function index(Request $request): ApiResponse
    {
        $query = AdministrativeRequest::query()
            ->with('approvals')
            ->whereIn('status', [
                AdministrativeRequest::STATUS_SUBMITTED,
                AdministrativeRequest::STATUS_UNDER_REVIEW,
            ])
            ->whereHas('approvals', fn ($approval) => $approval->where('status', RequestApproval::STATUS_PENDING))
            ->latest('submitted_at');

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->string('request_type'));
        }

        return ApiResponse::success([
            'requests' => $query->get()
                ->map(fn (AdministrativeRequest $item): array => $this->presenter->request(
                    $item,
                    $this->conflictCount($item),
                ))
                ->values()
                ->all(),
        ]);
    }

    public function decide(Request $request, int $id): ApiResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected,needs_revision'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $actorId = (int) $request->user()->getAuthIdentifier();

        $item = $this->decider->execute(
            $id,
            $actorId,
            (string) $request->user()->role,
            $validated['decision'],
            $validated['note'] ?? null,
        );

        return ApiResponse::success(
            ['request' => $this->presenter->request($item)],
            'Keputusan pengajuan disimpan.',
        );
    }

    private function conflictCount(AdministrativeRequest $request): int
    {
        if (! in_array($request->request_type, [
            AdministrativeRequest::TYPE_CUTI,
            AdministrativeRequest::TYPE_IZIN,
        ], true)) {
            return 0;
        }

        $employee = $this->employees->findById($request->employee_id);
        if ($employee?->divisionId === null) {
            return 0;
        }

        $colleagues = $this->employees->activeEmployeeIdsInDivision($employee->divisionId);
        if ($colleagues === []) {
            return 0;
        }

        return AdministrativeRequest::query()
            ->whereKeyNot($request->id)
            ->whereIn('employee_id', $colleagues)
            ->whereIn('request_type', [
                AdministrativeRequest::TYPE_CUTI,
                AdministrativeRequest::TYPE_IZIN,
            ])
            ->whereIn('status', [
                AdministrativeRequest::STATUS_SUBMITTED,
                AdministrativeRequest::STATUS_UNDER_REVIEW,
                AdministrativeRequest::STATUS_APPROVED,
            ])
            ->whereDate('start_date', '<=', $request->end_date)
            ->whereDate('end_date', '>=', $request->start_date)
            ->count();
    }
}
