<?php

namespace App\Modules\Leave\Application\ResubmitRequest;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Leave\Application\Exceptions\RequestNotFound;
use App\Modules\Leave\Application\Exceptions\RequestStateConflict;
use App\Modules\Leave\Application\SubmitRequest\SubmitAdministrativeRequest;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use Illuminate\Support\Facades\DB;

final class ResubmitAdministrativeRequest
{
    public function __construct(
        private readonly SubmitAdministrativeRequest $submitter,
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

        if ($request->status !== AdministrativeRequest::STATUS_NEEDS_REVISION) {
            throw new RequestStateConflict('Hanya pengajuan yang perlu revisi dapat diajukan ulang.');
        }

        return DB::transaction(function () use ($request, $employee, $actorUserId): AdministrativeRequest {
            $round = (int) $request->approvals()->max('round') + 1;

            $request->update([
                'status' => AdministrativeRequest::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'decided_at' => null,
                'cancelled_at' => null,
            ]);

            $this->submitter->createApprovalSteps($request, $employee, $round);

            $this->auditLogger->log(
                'administrative_request',
                (string) $request->id,
                'administrative_request.resubmitted',
                null,
                ['round' => $round, 'status' => AdministrativeRequest::STATUS_SUBMITTED],
                null,
                $actorUserId,
            );

            return $request->fresh(['approvals']);
        });
    }
}
