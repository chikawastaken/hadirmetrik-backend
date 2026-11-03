<?php

namespace App\Modules\Tasks\Application\StartTask;

use App\Modules\Tasks\Application\Exceptions\TaskNotFound;
use App\Modules\Tasks\Application\Exceptions\TaskStateConflict;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;

final class StartTaskUseCase
{
    public function execute(int $taskId, int $employeeId): Task
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

        if ($assignment->status !== TaskAssignment::STATUS_ASSIGNED) {
            throw new TaskStateConflict;
        }

        $assignment->update([
            'status' => TaskAssignment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        return $task->fresh();
    }
}
