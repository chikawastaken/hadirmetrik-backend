<?php

namespace App\Modules\Tasks\Application\SubmitTask;

use App\Modules\Tasks\Application\Exceptions\EvidenceRequired;
use App\Modules\Tasks\Application\Exceptions\TaskNotFound;
use App\Modules\Tasks\Application\Exceptions\TaskStateConflict;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use App\Modules\Tasks\Infrastructure\Models\TaskAttachment;
use App\Modules\Tasks\Infrastructure\Models\TaskSubmission;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SubmitTaskUseCase
{
    /**
     * @param  list<array<string, mixed>>  $checklist
     * @param  list<UploadedFile>  $evidenceFiles
     * @param  list<int>  $attachmentIds
     */
    public function execute(
        int $taskId,
        int $employeeId,
        ?string $note = null,
        array $checklist = [],
        array $evidenceFiles = [],
        array $attachmentIds = [],
        ?int $uploadedByUserId = null,
    ): Task {
        try {
            return DB::transaction(function () use (
                $taskId,
                $employeeId,
                $note,
                $checklist,
                $evidenceFiles,
                $attachmentIds,
                $uploadedByUserId,
            ): Task {
                $assignment = TaskAssignment::query()
                    ->where('task_id', $taskId)
                    ->where('employee_id', $employeeId)
                    ->lockForUpdate()
                    ->first();

                if ($assignment === null) {
                    throw new TaskNotFound;
                }

                $task = Task::query()->lockForUpdate()->find($assignment->task_id);

                if ($task === null) {
                    throw new TaskNotFound;
                }

                if ($task->isTerminal()) {
                    throw new TaskStateConflict('Tugas sudah ditutup atau dibatalkan.');
                }

                if (! in_array($assignment->status, [
                    TaskAssignment::STATUS_IN_PROGRESS,
                    TaskAssignment::STATUS_REVISION_REQUIRED,
                ], true)) {
                    throw new TaskStateConflict;
                }

                $pending = TaskSubmission::query()
                    ->where('task_assignment_id', $assignment->id)
                    ->where('review_status', TaskSubmission::REVIEW_PENDING)
                    ->lockForUpdate()
                    ->exists();

                if ($pending) {
                    throw new TaskStateConflict('Pengajuan sebelumnya masih menunggu penilaian.');
                }

                $attachmentIds = array_values(array_map('intval', $attachmentIds));

                if ($attachmentIds !== []) {
                    $validCount = TaskAttachment::query()
                        ->where('task_id', $task->id)
                        ->whereIn('id', $attachmentIds)
                        ->where('kind', TaskAttachment::KIND_EVIDENCE)
                        ->where('uploaded_by', $uploadedByUserId ?? $task->created_by)
                        ->count();

                    if ($validCount !== count($attachmentIds)) {
                        throw new EvidenceRequired('Lampiran bukti tidak valid untuk tugas ini.');
                    }
                }

                if ($task->requires_photo_proof && $evidenceFiles === [] && $attachmentIds === []) {
                    throw new EvidenceRequired;
                }

                $submission = TaskSubmission::create([
                    'task_assignment_id' => $assignment->id,
                    'note' => $note,
                    'checklist' => $checklist,
                    'review_status' => TaskSubmission::REVIEW_PENDING,
                    'submitted_at' => now(),
                ]);

                foreach ($evidenceFiles as $file) {
                    $path = 'tasks/'.$task->id.'/submissions/'.$submission->id;
                    $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
                    $objectKey = $file->storeAs($path, $name, 'local');

                    TaskAttachment::create([
                        'task_id' => $task->id,
                        'task_submission_id' => $submission->id,
                        'kind' => TaskAttachment::KIND_EVIDENCE,
                        'storage_disk' => 'local',
                        'object_key' => $objectKey,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'size_bytes' => $file->getSize(),
                        'sha256' => hash_file('sha256', $file->getRealPath()) ?: str_repeat('0', 64),
                        'uploaded_by' => $uploadedByUserId ?? $task->created_by,
                    ]);
                }

                if ($attachmentIds !== []) {
                    TaskAttachment::query()
                        ->where('task_id', $task->id)
                        ->whereIn('id', $attachmentIds)
                        ->where('uploaded_by', $uploadedByUserId ?? $task->created_by)
                        ->update(['task_submission_id' => $submission->id]);
                }

                $assignment->update(['status' => TaskAssignment::STATUS_SUBMITTED]);

                return $task->fresh();
            });
        } catch (UniqueConstraintViolationException) {
            throw new TaskStateConflict('Pengajuan sebelumnya masih menunggu penilaian.');
        }
    }
}
