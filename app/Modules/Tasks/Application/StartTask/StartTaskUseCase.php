<?php

namespace App\Modules\Tasks\Application\StartTask;

use App\Modules\Tasks\Application\Exceptions\TaskNotFound;
use App\Modules\Tasks\Application\Exceptions\TaskStateConflict;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use Illuminate\Support\Facades\DB;

final class StartTaskUseCase
{
    public function execute(int $taskId, int $employeeId): Task
    {
        return DB::transaction(function () use ($taskId, $employeeId): Task {
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

            if ($assignment->status !== TaskAssignment::STATUS_ASSIGNED) {
                throw new TaskStateConflict;
            }

            $assignment->update([
                'status' => TaskAssignment::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            return $task->fresh();
        });
    }
}
