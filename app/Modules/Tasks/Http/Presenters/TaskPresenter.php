<?php

namespace App\Modules\Tasks\Http\Presenters;

use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Identity\Contracts\UserDirectory;
use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use App\Modules\Tasks\Infrastructure\Models\TaskAttachment;
use App\Modules\Tasks\Infrastructure\Models\TaskComment;

final class TaskPresenter
{
    public function __construct(
        private readonly EmployeeDirectory $employees,
        private readonly UserDirectory $users,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'status' => $task->status,
            'requires_photo_proof' => $task->requires_photo_proof,
            'division_id' => $task->division_id,
            'created_by' => $task->created_by,
            'due_at' => $task->due_at?->toIso8601String(),
            'cancelled_at' => $task->cancelled_at?->toIso8601String(),
            'created_at' => $task->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Task $task): array
    {
        $task->loadMissing(['assignments', 'attachments', 'comments']);

        $employeeNames = $this->employeeNames($task->assignments->all());
        $authorNames = $this->users->namesByIds(
            $task->comments->pluck('author_user_id')->unique()->values()->all()
        );

        return $this->summary($task) + [
            'checklist_template' => $task->checklist_template ?? [],
            'assignments' => $task->assignments
                ->map(fn (TaskAssignment $assignment): array => $this->assignment($assignment, $employeeNames))
                ->values()
                ->all(),
            'attachments' => $task->attachments
                ->map(fn (TaskAttachment $attachment): array => $this->attachment($attachment))
                ->values()
                ->all(),
            'comments' => $task->comments
                ->sortBy('created_at')
                ->map(fn (TaskComment $comment): array => $this->comment($comment, $authorNames))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assignment(TaskAssignment $assignment, array $employeeNames = []): array
    {
        return [
            'id' => $assignment->id,
            'task_id' => $assignment->task_id,
            'employee_id' => $assignment->employee_id,
            'employee_name' => $employeeNames[$assignment->employee_id] ?? null,
            'status' => $assignment->status,
            'progress_percent' => $assignment->progress_percent,
            'assigned_at' => $assignment->assigned_at?->toIso8601String(),
            'started_at' => $assignment->started_at?->toIso8601String(),
            'completed_at' => $assignment->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attachment(TaskAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'task_submission_id' => $attachment->task_submission_id,
            'kind' => $attachment->kind,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
            'created_at' => $attachment->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, string>  $authorNames
     * @return array<string, mixed>
     */
    public function comment(TaskComment $comment, array $authorNames = []): array
    {
        return [
            'id' => $comment->id,
            'author_user_id' => $comment->author_user_id,
            'author_name' => $authorNames[$comment->author_user_id] ?? null,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  list<TaskAssignment>  $assignments
     * @return array<int, string>
     */
    private function employeeNames(array $assignments): array
    {
        $ids = array_values(array_unique(array_map(
            fn (TaskAssignment $assignment): int => (int) $assignment->employee_id,
            $assignments,
        )));

        $names = [];
        foreach ($this->employees->findActiveByIds($ids) as $summary) {
            $names[$summary->id] = $summary->name;
        }

        return $names;
    }
}
