<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use App\Modules\Tasks\Infrastructure\Models\TaskAttachment;
use App\Modules\Tasks\Infrastructure\Models\TaskSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class TaskSubmissionTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    private function makeTask(array $attributes = []): Task
    {
        return Task::create(array_merge([
            'created_by' => $this->makeHrUser()->id,
            'title' => 'Tugas '.uniqid(),
            'priority' => 'medium',
            'status' => Task::STATUS_ASSIGNED,
            'requires_photo_proof' => false,
            'checklist_template' => [],
        ], $attributes));
    }

    private function makeAssignment(Task $task, int $employeeId, string $status = 'in_progress'): TaskAssignment
    {
        return $task->assignments()->create([
            'employee_id' => $employeeId,
            'status' => $status,
            'started_at' => now(),
        ]);
    }

    public function test_assignee_submits_task_for_review(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $assignment = $this->makeAssignment($task, $employee->id);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions', [
                'note' => 'Sudah selesai, mohon diperiksa.',
                'checklist' => [
                    ['key' => 'c1', 'label' => 'Langkah 1', 'done' => true],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.task.assignments.0.status', 'submitted')
            ->assertJsonPath('data.submission.review_status', TaskSubmission::REVIEW_PENDING);
        $this->assertNotNull($response->json('data.submission.id'));

        $this->assertSame('submitted', $assignment->refresh()->status);
        $this->assertDatabaseHas('task_submissions', [
            'task_assignment_id' => $assignment->id,
            'review_status' => TaskSubmission::REVIEW_PENDING,
        ]);
    }

    public function test_submission_requires_evidence_when_task_requires_photo_proof(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['requires_photo_proof' => true]);
        $this->makeAssignment($task, $employee->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions', ['note' => 'Selesai'])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'EVIDENCE_REQUIRED');
    }

    public function test_submission_accepts_existing_evidence_attachment(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['requires_photo_proof' => true]);
        $assignment = $this->makeAssignment($task, $employee->id);

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'kind' => TaskAttachment::KIND_EVIDENCE,
            'storage_disk' => 'local',
            'object_key' => 'tasks/'.$task->id.'/evidence.jpg',
            'original_name' => 'evidence.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'sha256' => str_repeat('a', 64),
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions', [
                'attachment_ids' => [$attachment->id],
            ])
            ->assertStatus(201);

        $submission = TaskSubmission::query()->where('task_assignment_id', $assignment->id)->firstOrFail();
        $this->assertSame($submission->id, $attachment->refresh()->task_submission_id);
    }

    public function test_submission_cannot_reuse_another_users_attachment(): void
    {
        $userA = $this->makeEmployeeUser();
        $employeeA = $this->makeEmployeeRecord(['user_id' => $userA->id]);
        $task = $this->makeTask(['requires_photo_proof' => true]);
        $this->makeAssignment($task, $employeeA->id);

        $userB = $this->makeEmployeeUser();
        $employeeB = $this->makeEmployeeRecord(['user_id' => $userB->id]);
        $assignmentB = $task->assignments()->create([
            'employee_id' => $employeeB->id,
            'status' => 'submitted',
        ]);

        $attachmentFromB = TaskAttachment::create([
            'task_id' => $task->id,
            'kind' => TaskAttachment::KIND_EVIDENCE,
            'storage_disk' => 'local',
            'object_key' => 'tasks/'.$task->id.'/b-evidence.jpg',
            'original_name' => 'b-evidence.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'sha256' => str_repeat('b', 64),
            'uploaded_by' => $userB->id,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions', [
                'attachment_ids' => [$attachmentFromB->id],
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'EVIDENCE_REQUIRED');

        $this->assertNull($attachmentFromB->refresh()->task_submission_id);
        $this->assertSame(0, TaskSubmission::query()->where('task_assignment_id', $assignmentB->id)->count());
    }

    public function test_pending_submission_guard_rejects_a_new_submission(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $assignment = $this->makeAssignment($task, $employee->id);

        TaskSubmission::create([
            'task_assignment_id' => $assignment->id,
            'checklist' => [],
            'review_status' => TaskSubmission::REVIEW_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(409)
            ->assertJsonPath('errors.code.0', 'STATE_NOT_TRANSITIONABLE');
    }

    public function test_second_pending_submission_is_rejected(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $this->makeAssignment($task, $employee->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(409)
            ->assertJsonPath('errors.code.0', 'STATE_NOT_TRANSITIONABLE');
    }

    public function test_revision_submission_returns_to_under_review(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $hr = $this->makeHrUser();
        $task = $this->makeTask(['created_by' => $hr->id]);
        $assignment = $this->makeAssignment($task, $employee->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(201);

        $submission = TaskSubmission::query()->where('task_assignment_id', $assignment->id)->firstOrFail();

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submission->id.'/review', [
                'decision' => 'revision_requested',
                'note' => 'Lampiran kurang jelas.',
            ])
            ->assertOk()
            ->assertJsonPath('data.task.assignments.0.status', 'revision_required');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions', ['note' => 'Sudah diperbaiki'])
            ->assertStatus(201)
            ->assertJsonPath('data.task.assignments.0.status', 'submitted');

        $this->assertSame(2, TaskSubmission::query()->where('task_assignment_id', $assignment->id)->count());
    }
}
