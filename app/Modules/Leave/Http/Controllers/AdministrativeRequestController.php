<?php

namespace App\Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leave\Application\CancelRequest\CancelAdministrativeRequest;
use App\Modules\Leave\Application\Exceptions\RequestNotFound;
use App\Modules\Leave\Application\ResubmitRequest\ResubmitAdministrativeRequest;
use App\Modules\Leave\Application\SubmitRequest\SubmitAdministrativeRequest;
use App\Modules\Leave\Application\Summary\GetLeaveSummary;
use App\Modules\Leave\Application\Support\LeaveAccess;
use App\Modules\Leave\Http\Presenters\AdministrativeRequestPresenter;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;

final class AdministrativeRequestController extends Controller
{
    public function __construct(
        private readonly LeaveAccess $access,
        private readonly AdministrativeRequestPresenter $presenter,
        private readonly SubmitAdministrativeRequest $submitter,
        private readonly CancelAdministrativeRequest $canceller,
        private readonly ResubmitAdministrativeRequest $resubmitter,
        private readonly GetLeaveSummary $summary,
    ) {}

    public function index(Request $request): ApiResponse
    {
        $employee = $this->access->employeeFor($this->actorId($request));

        $query = AdministrativeRequest::query()
            ->with('approvals')
            ->where('employee_id', $employee->id)
            ->latest();

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->string('request_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ApiResponse::success([
            'requests' => $query->get()
                ->map(fn (AdministrativeRequest $item): array => $this->presenter->request($item))
                ->values()
                ->all(),
        ]);
    }

    public function summary(Request $request): ApiResponse
    {
        $employee = $this->access->employeeFor($this->actorId($request));

        return ApiResponse::success(['summary' => $this->summary->execute($employee)]);
    }

    public function show(Request $request, int $id): ApiResponse
    {
        $employee = $this->access->employeeFor($this->actorId($request));

        $item = AdministrativeRequest::query()
            ->with('approvals')
            ->where('employee_id', $employee->id)
            ->find($id);

        if ($item === null) {
            throw new RequestNotFound;
        }

        return ApiResponse::success(['request' => $this->presenter->request($item)]);
    }

    public function store(Request $request): ApiResponse
    {
        $validated = $request->validate([
            'request_type' => ['required', 'in:cuti,izin,lembur'],
            'category' => ['nullable', 'string', 'max:24'],
            'special_reason' => ['nullable', 'string', 'max:24'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'day_portion' => ['nullable', 'in:full,morning,afternoon'],
            'overtime_date' => ['nullable', 'date'],
            'overtime_hours' => ['nullable', 'numeric', 'gt:0', 'max:12'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $employee = $this->access->employeeFor($this->actorId($request));
        $item = $this->submitter->execute($employee, $validated, $this->actorId($request));

        return ApiResponse::success(
            ['request' => $this->presenter->request($item)],
            'Pengajuan berhasil dikirim.',
            201,
        );
    }

    public function cancel(Request $request, int $id): ApiResponse
    {
        $employee = $this->access->employeeFor($this->actorId($request));
        $item = $this->canceller->execute($id, $employee, $this->actorId($request));

        return ApiResponse::success(['request' => $this->presenter->request($item)], 'Pengajuan dibatalkan.');
    }

    public function resubmit(Request $request, int $id): ApiResponse
    {
        $employee = $this->access->employeeFor($this->actorId($request));
        $item = $this->resubmitter->execute($id, $employee, $this->actorId($request));

        return ApiResponse::success(['request' => $this->presenter->request($item)], 'Pengajuan dikirim ulang.');
    }

    private function actorId(Request $request): int
    {
        return (int) $request->user()->getAuthIdentifier();
    }
}
