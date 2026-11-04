<?php

namespace App\Modules\Tasks\Application\UpdateProgress;

use App\Modules\Tasks\Application\Exceptions\TaskNotFound;
use App\Modules\Tasks\Application\Exceptions\TaskStateConflict;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use Illuminate\Support\Facades\DB;

final class UpdateProgressUseCase
{
    private const ALLOWED = [
        TaskAssignment::STATUS_ASSIGNED,
        TaskAssignment::STATUS_IN_PROGRESS,
        TaskAssignment::STATUS_REVISION_REQUIRED,
    ];

    /**
     * @param  list<array<string, mixed>>  $checklist
     */
    public function execute(int $taskId, int $employeeId, int $progress, array $checklist = []): Task
    {
        return DB::transaction(function () use ($taskId, $employeeId, $progress, $checklist): Task {
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

            if (! in_array($assignment->status, self::ALLOWED, true)) {
                throw new TaskStateConflict;
            }

            $assignment->update([
                'progress_percent' => max(0, min(100, $progress)),
                'checklist' => $checklist,
                'status' => TaskAssignment::STATUS_IN_PROGRESS,
                'started_at' => $assignment->started_at ?? now(),
            ]);

            return $task->fresh();
        });
    }
}
