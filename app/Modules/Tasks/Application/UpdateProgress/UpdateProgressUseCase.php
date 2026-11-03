<?php

namespace App\Modules\Tasks\Application\UpdateProgress;

use App\Modules\Tasks\Application\Exceptions\TaskNotFound;
use App\Modules\Tasks\Application\Exceptions\TaskStateConflict;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;

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
        $assignment = TaskAssignment::query()
            ->with('task')
            ->where('task_id', $taskId)
            ->where('employee_id', $employeeId)
            ->first();

        if ($assignment === null) {
            throw new TaskNotFound;
        }

        $task = $assignment->task;

        if ($task->isTerminal()) {
            throw new TaskStateConflict('Tugas sudah ditutup atau dibatalkan.');
        }

        if (! in_array($assignment->status, self::ALLOWED, true)) {
            throw new TaskStateConflict;
        }

        $assignment->update([
            'progress_percent' => max(0, min(100, $progress)),
            'status' => TaskAssignment::STATUS_IN_PROGRESS,
            'started_at' => $assignment->started_at ?? now(),
        ]);

        return $task->fresh();
    }
}
