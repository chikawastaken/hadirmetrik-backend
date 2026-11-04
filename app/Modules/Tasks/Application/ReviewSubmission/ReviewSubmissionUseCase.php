<?php

namespace App\Modules\Tasks\Application\ReviewSubmission;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Tasks\Application\Exceptions\ReviewForbidden;
use App\Modules\Tasks\Application\Exceptions\ReviewValidationFailed;
use App\Modules\Tasks\Application\Exceptions\TaskNotFound;
use App\Modules\Tasks\Application\Exceptions\TaskStateConflict;
use App\Modules\Tasks\Application\Support\TaskAccess;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use App\Modules\Tasks\Infrastructure\Models\TaskSubmission;
use Illuminate\Support\Facades\DB;

final class ReviewSubmissionUseCase
{
    public function __construct(
        private readonly TaskAccess $access,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(
        int $submissionId,
        int $reviewerUserId,
        string $role,
        string $decision,
        ?int $rating = null,
        ?string $note = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Task {
        return DB::transaction(function () use (
            $submissionId,
            $reviewerUserId,
            $role,
            $decision,
            $rating,
            $note,
            $ipAddress,
            $userAgent,
        ): Task {
            $submission = TaskSubmission::query()
                ->lockForUpdate()
                ->find($submissionId);

            if ($submission === null) {
                throw new TaskNotFound('Pengajuan tidak ditemukan.');
            }

            $assignment = TaskAssignment::query()
                ->lockForUpdate()
                ->find($submission->task_assignment_id);

            if ($assignment === null) {
                throw new TaskNotFound('Pengajuan tidak ditemukan.');
            }

            $task = Task::query()->lockForUpdate()->find($assignment->task_id);

            if ($task === null) {
                throw new TaskNotFound;
            }

            if (! $this->access->canReview($reviewerUserId, $role, $task)) {
                throw new ReviewForbidden;
            }

            if ($task->isTerminal() || $assignment->status === TaskAssignment::STATUS_CANCELLED) {
                throw new TaskStateConflict('Tugas sudah ditutup atau dibatalkan.');
            }

            if ($submission->review_status !== TaskSubmission::REVIEW_PENDING) {
                throw new TaskStateConflict('Pengajuan ini sudah dinilai.');
            }

            if ($decision === 'approved') {
                if ($rating === null) {
                    throw new ReviewValidationFailed('Penilaian wajib diisi.', 'RATING_REQUIRED');
                }

                if ($rating < 1 || $rating > 5) {
                    throw new ReviewValidationFailed('Penilaian harus antara 1 sampai 5.', 'RATING_INVALID');
                }
            } elseif ($decision === 'revision_requested') {
                if ($note === null || trim($note) === '') {
                    throw new ReviewValidationFailed('Catatan revisi wajib diisi.', 'NOTE_REQUIRED');
                }
            } else {
                throw new ReviewValidationFailed('Keputusan penilaian tidak dikenal.', 'DECISION_INVALID');
            }

            $before = ['review_status' => $submission->review_status];

            $submission->update([
                'review_status' => $decision === 'approved'
                    ? TaskSubmission::REVIEW_APPROVED
                    : TaskSubmission::REVIEW_REVISION_REQUESTED,
                'review_rating' => $decision === 'approved' ? $rating : null,
                'review_note' => $note,
                'reviewed_by' => $reviewerUserId,
                'reviewed_at' => now(),
            ]);

            if ($decision === 'approved') {
                $assignment->update([
                    'status' => TaskAssignment::STATUS_COMPLETED,
                    'progress_percent' => 100,
                    'completed_at' => now(),
                ]);
            } else {
                $assignment->update([
                    'status' => TaskAssignment::STATUS_REVISION_REQUIRED,
                ]);
            }

            $open = TaskAssignment::query()
                ->where('task_id', $task->id)
                ->whereNotIn('status', [
                    TaskAssignment::STATUS_COMPLETED,
                    TaskAssignment::STATUS_CANCELLED,
                ])
                ->exists();

            if (! $open && $task->status !== Task::STATUS_CANCELLED) {
                $task->update(['status' => Task::STATUS_CLOSED]);
            }

            $this->auditLogger->log(
                'task_submission',
                (string) $submission->id,
                'task_submission.reviewed',
                $before,
                [
                    'review_status' => $submission->review_status,
                    'review_rating' => $submission->review_rating,
                    'review_note' => $submission->review_note,
                    'assignment_status' => $assignment->status,
                    'task_status' => $task->status,
                ],
                null,
                $reviewerUserId,
                $ipAddress,
                $userAgent,
            );

            return $task->fresh();
        });
    }
}
