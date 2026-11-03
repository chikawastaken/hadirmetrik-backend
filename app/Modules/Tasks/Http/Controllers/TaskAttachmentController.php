<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tasks\Application\Support\TaskAccess;
use App\Modules\Tasks\Http\Presenters\TaskPresenter;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAttachment;
use App\Modules\Tasks\Infrastructure\Models\TaskSubmission;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TaskAttachmentController extends Controller
{
    public function __construct(
        private readonly TaskAccess $access,
        private readonly TaskPresenter $presenter,
    ) {}

    public function store(Request $request, int $id): ApiResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,xlsx', 'max:10240'],
            'kind' => ['required', 'string', 'in:brief,evidence'],
            'submission_id' => ['required_if:kind,evidence', 'nullable', 'integer'],
        ]);

        $user = $request->user();
        $task = Task::query()->find($id);

        if ($task === null || ! $this->access->canView(
            (int) $user->getAuthIdentifier(),
            (string) $user->role,
            $task,
        )) {
            return ApiResponse::error('Tugas tidak ditemukan.', 404);
        }

        $submissionId = null;

        if ($validated['kind'] === TaskAttachment::KIND_EVIDENCE) {
            $submission = TaskSubmission::query()
                ->whereKey((int) $validated['submission_id'])
                ->whereHas('assignment', fn ($query) => $query->where('task_id', $task->id))
                ->first();

            if ($submission === null) {
                throw ValidationException::withMessages([
                    'submission_id' => ['Pengajuan tidak ditemukan pada tugas ini.'],
                ]);
            }

            $submissionId = $submission->id;
        }

        $file = $request->file('file');
        $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $objectKey = $file->storeAs('tasks/'.$task->id, $name, 'local');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'task_submission_id' => $submissionId,
            'kind' => $validated['kind'],
            'storage_disk' => 'local',
            'object_key' => $objectKey,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()) ?: str_repeat('0', 64),
            'uploaded_by' => $user->getAuthIdentifier(),
        ]);

        return ApiResponse::success(
            ['attachment' => $this->presenter->attachment($attachment)],
            'Lampiran diunggah.',
            201,
        );
    }
}
