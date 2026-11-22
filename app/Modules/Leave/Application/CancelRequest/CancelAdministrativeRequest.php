<?php

namespace App\Modules\Leave\Application\CancelRequest;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Leave\Application\Exceptions\RequestNotFound;
use App\Modules\Leave\Application\Exceptions\RequestStateConflict;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use App\Modules\Leave\Infrastructure\Models\RequestApproval;
use Illuminate\Support\Facades\DB;

final class CancelAdministrativeRequest
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(int $requestId, EmployeeSummary $employee, int $actorUserId): AdministrativeRequest
    {
        $request = AdministrativeRequest::query()
            ->where('employee_id', $employee->id)
            ->find($requestId);

        if ($request === null) {
            throw new RequestNotFound;
        }

        if (! in_array($request->status, [
            AdministrativeRequest::STATUS_DRAFT,
            AdministrativeRequest::STATUS_SUBMITTED,
            AdministrativeRequest::STATUS_UNDER_REVIEW,
            AdministrativeRequest::STATUS_NEEDS_REVISION,
        ], true)) {
            throw new RequestStateConflict('Pengajuan yang sudah diputuskan tidak dapat dibatalkan.');
        }

        return DB::transaction(function () use ($request, $actorUserId): AdministrativeRequest {
            $before = $request->status;

            $request->update([
                'status' => AdministrativeRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $request->approvals()
                ->where('status', RequestApproval::STATUS_PENDING)
                ->update(['status' => RequestApproval::STATUS_SKIPPED]);

            $this->auditLogger->log(
                'administrative_request',
                (string) $request->id,
                'administrative_request.cancelled',
                ['status' => $before],
                ['status' => AdministrativeRequest::STATUS_CANCELLED],
                null,
                $actorUserId,
            );

            return $request->fresh(['approvals']);
        });
    }
}
