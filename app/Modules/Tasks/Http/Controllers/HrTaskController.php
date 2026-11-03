<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Tasks\Application\Exceptions\TaskForbidden;
use App\Modules\Tasks\Application\Exceptions\TaskNotFound;
use App\Modules\Tasks\Application\Exceptions\TaskStateConflict;
use App\Modules\Tasks\Application\Support\TaskAccess;
use App\Modules\Tasks\Http\Presenters\TaskPresenter;
use App\Modules\Tasks\Http\Requests\StoreTaskRequest;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HrTaskController extends Controller
{
    public function __construct(
        private readonly EmployeeDirectory $employees,
        private readonly TaskPresenter $presenter,
        private readonly TaskAccess $access,
    ) {}

    public function index(Request $request): ApiResponse
    {
        $query = Task::query()->with('assignments')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $tasks = $query->get()->map(fn (Task $task): array => $this->presenter->detail($task));

        return ApiResponse::success(['tasks' => $tasks]);
    }

    public function options(): ApiResponse
    {
        return ApiResponse::success([
            'priorities' => [
                ['value' => 'high', 'label' => 'Tinggi'],
                ['value' => 'medium', 'label' => 'Sedang'],
                ['value' => 'low', 'label' => 'Rendah'],
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): ApiResponse
    {
        $validated = $request->validated();
        $assigneeIds = array_map('intval', $validated['assignee_ids']);

        $active = $this->employees->findActiveByIds($assigneeIds);
        if (count($active) !== count($assigneeIds)) {
            throw ValidationException::withMessages([
                'assignee_ids' => ['Penugasan hanya boleh untuk karyawan aktif yang terdaftar.'],
            ]);
        }

        $user = $request->user();

        $task = DB::transaction(function () use ($validated, $assigneeIds, $user): Task {
            $task = Task::create([
                'created_by' => $user->getAuthIdentifier(),
                'division_id' => $validated['division_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'],
                'status' => Task::STATUS_ASSIGNED,
                'requires_photo_proof' => (bool) ($validated['requires_photo_proof'] ?? false),
                'checklist_template' => $validated['checklist_template'] ?? [],
                'due_at' => $validated['due_at'] ?? null,
            ]);

            foreach ($assigneeIds as $employeeId) {
                $task->assignments()->create([
                    'employee_id' => $employeeId,
                    'status' => 'assigned',
                    'progress_percent' => 0,
                    'assigned_at' => now(),
                ]);
            }

            return $task;
        });

        return ApiResponse::success(
            ['task' => $this->presenter->detail($task->fresh())],
            'Tugas berhasil dibuat.',
            201,
        );
    }

    public function cancel(Request $request, int $id): ApiResponse
    {
        $task = Task::query()->find($id);

        if ($task === null) {
            throw new TaskNotFound;
        }

        $user = $request->user();

        if (! $this->access->canCancel(
            (int) $user->getAuthIdentifier(),
            (string) $user->role,
            $task,
        )) {
            throw new TaskForbidden('Anda tidak berwenang membatalkan tugas ini.');
        }

        if (! in_array($task->status, [Task::STATUS_DRAFT, Task::STATUS_ASSIGNED], true)) {
            throw new TaskStateConflict('Tugas yang sudah selesai atau dibatalkan tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($task): void {
            $task->update([
                'status' => Task::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            TaskAssignment::query()
                ->where('task_id', $task->id)
                ->whereNotIn('status', [
                    TaskAssignment::STATUS_COMPLETED,
                    TaskAssignment::STATUS_CANCELLED,
                ])
                ->update(['status' => TaskAssignment::STATUS_CANCELLED]);
        });

        return ApiResponse::success(
            ['task' => $this->presenter->detail($task->fresh())],
            'Tugas dibatalkan.',
        );
    }
}
