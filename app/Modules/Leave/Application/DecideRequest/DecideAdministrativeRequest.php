<?php

namespace App\Modules\Leave\Application\DecideRequest;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Leave\Application\Exceptions\RequestForbidden;
use App\Modules\Leave\Application\Exceptions\RequestNotFound;
use App\Modules\Leave\Application\Exceptions\RequestStateConflict;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use App\Modules\Leave\Infrastructure\Models\RequestApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DecideAdministrativeRequest
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(
        int $requestId,
        int $actorUserId,
        string $role,
        string $decision,
        ?string $note,
    ): AdministrativeRequest {
        $request = AdministrativeRequest::query()->with('approvals')->find($requestId);

        if ($request === null) {
            throw new RequestNotFound;
        }

        if (! $request->isPending()) {
            throw new RequestStateConflict('Pengajuan ini sudah diputuskan.');
        }

        $current = $request->approvals
            ->where('status', RequestApproval::STATUS_PENDING)
            ->sortBy('step_order')
            ->first();

        if ($current === null) {
            throw new RequestStateConflict('Tidak ada langkah persetujuan yang menunggu.');
        }

        $isHr = in_array($role, ['hr', 'admin'], true);
        if (! $isHr && (int) $current->approver_user_id !== $actorUserId) {
            throw new RequestForbidden;
        }

        if (in_array($decision, ['rejected', 'needs_revision'], true) && trim((string) $note) === '') {
            throw ValidationException::withMessages([
                'note' => ['Catatan wajib diisi untuk keputusan ini.'],
            ]);
        }

        return DB::transaction(function () use ($request, $current, $decision, $note, $actorUserId): AdministrativeRequest {
            $now = now();

            if ($decision === 'approved') {
                $current->update([
                    'status' => RequestApproval::STATUS_APPROVED,
                    'note' => $note,
                    'approver_user_id' => $current->approver_user_id ?? $actorUserId,
                    'decided_at' => $now,
                ]);

                $hasNext = $request->approvals()
                    ->where('round', $current->round)
                    ->where('step_order', '>', $current->step_order)
                    ->where('status', RequestApproval::STATUS_PENDING)
                    ->exists();

                $request->update([
                    'status' => $hasNext
                        ? AdministrativeRequest::STATUS_UNDER_REVIEW
                        : AdministrativeRequest::STATUS_APPROVED,
                    'decided_at' => $hasNext ? null : $now,
                ]);
            } else {
                $status = $decision === 'rejected'
                    ? RequestApproval::STATUS_REJECTED
                    : RequestApproval::STATUS_NEEDS_REVISION;

                $current->update([
                    'status' => $status,
                    'note' => $note,
                    'approver_user_id' => $current->approver_user_id ?? $actorUserId,
                    'decided_at' => $now,
                ]);

                $request->approvals()
                    ->where('round', $current->round)
                    ->where('status', RequestApproval::STATUS_PENDING)
                    ->update(['status' => RequestApproval::STATUS_SKIPPED]);

                $request->update([
                    'status' => $decision === 'rejected'
                        ? AdministrativeRequest::STATUS_REJECTED
                        : AdministrativeRequest::STATUS_NEEDS_REVISION,
                    'decided_at' => $decision === 'rejected' ? $now : null,
                ]);
            }

            $this->auditLogger->log(
                'administrative_request',
                (string) $request->id,
                'administrative_request.decided',
                null,
                [
                    'decision' => $decision,
                    'step_order' => $current->step_order,
                    'round' => $current->round,
                    'status' => $request->status,
                ],
                $note,
                $actorUserId,
            );

            return $request->fresh(['approvals']);
        });
    }
}
