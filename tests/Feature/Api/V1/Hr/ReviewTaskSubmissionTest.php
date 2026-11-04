<?php

namespace Tests\Feature\Api\V1\Hr;

use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAssignment;
use App\Modules\Tasks\Infrastructure\Models\TaskSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class ReviewTaskSubmissionTest extends TestCase
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

    public function test_reviewer_approves_submission_with_rating(): void
    {
        $hr = $this->makeHrUser();
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['created_by' => $hr->id]);
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(201);

        $submission = TaskSubmission::query()->where('task_assignment_id', $assignment->id)->firstOrFail();

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submission->id.'/review', [
                'decision' => 'approved',
                'rating' => 5,
                'note' => 'Kerja bagus.',
            ])
            ->assertOk()
            ->assertJsonPath('data.task.assignments.0.status', 'completed')
            ->assertJsonPath('data.task.assignments.0.progress_percent', 100);

        $submission->refresh();
        $this->assertSame(TaskSubmission::REVIEW_APPROVED, $submission->review_status);
        $this->assertSame(5, $submission->review_rating);
        $this->assertNotNull($assignment->refresh()->completed_at);
    }

    public function test_review_requires_note_for_revision(): void
    {
        $hr = $this->makeHrUser();
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['created_by' => $hr->id]);
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(201);

        $submission = TaskSubmission::query()->where('task_assignment_id', $assignment->id)->firstOrFail();

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submission->id.'/review', [
                'decision' => 'revision_requested',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'NOTE_REQUIRED');
    }

    public function test_reviewer_cannot_review_twice(): void
    {
        $hr = $this->makeHrUser();
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['created_by' => $hr->id]);
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(201);

        $submission = TaskSubmission::query()->where('task_assignment_id', $assignment->id)->firstOrFail();

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submission->id.'/review', [
                'decision' => 'approved',
                'rating' => 4,
            ])
            ->assertOk();

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submission->id.'/review', [
                'decision' => 'approved',
                'rating' => 4,
            ])
            ->assertStatus(409)
            ->assertJsonPath('errors.code.0', 'STATE_NOT_TRANSITIONABLE');
    }

    public function test_review_is_rejected_for_a_cancelled_task(): void
    {
        $hr = $this->makeHrUser();
        $task = $this->makeTask([
            'created_by' => $hr->id,
            'status' => Task::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        $employee = $this->makeEmployeeRecord();
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'submitted',
        ]);
        $submission = TaskSubmission::create([
            'task_assignment_id' => $assignment->id,
            'checklist' => [],
            'review_status' => TaskSubmission::REVIEW_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submission->id.'/review', [
                'decision' => 'approved',
                'rating' => 5,
            ])
            ->assertStatus(409)
            ->assertJsonPath('errors.code.0', 'STATE_NOT_TRANSITIONABLE');

        $this->assertSame(TaskSubmission::REVIEW_PENDING, $submission->refresh()->review_status);
        $this->assertSame('submitted', $assignment->refresh()->status);
    }

    public function test_review_is_audited(): void
    {
        $hr = $this->makeHrUser();
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['created_by' => $hr->id]);
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
            ->assertStatus(201);

        $submission = TaskSubmission::query()->where('task_assignment_id', $assignment->id)->firstOrFail();

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submission->id.'/review', [
                'decision' => 'approved',
                'rating' => 5,
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'task_submission',
            'entity_id' => (string) $submission->id,
            'action' => 'task_submission.reviewed',
            'actor_user_id' => $hr->id,
        ]);
    }

    public function test_task_closes_when_all_assignments_complete(): void
    {
        $hr = $this->makeHrUser();
        $task = $this->makeTask(['created_by' => $hr->id]);

        $assignments = [];
        foreach ([1, 2] as $index) {
            $user = $this->makeEmployeeUser();
            $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
            $assignment = $task->assignments()->create([
                'employee_id' => $employee->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
            $assignments[] = $assignment;

            $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/tasks/'.$task->id.'/submissions')
                ->assertStatus(201);
        }

        $submissions = TaskSubmission::query()
            ->whereIn('task_assignment_id', array_map(fn (TaskAssignment $a): int => $a->id, $assignments))
            ->orderBy('id')
            ->get();

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submissions[0]->id.'/review', [
                'decision' => 'approved',
                'rating' => 5,
            ])
            ->assertOk();

        $this->assertSame(Task::STATUS_ASSIGNED, $task->refresh()->status);

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/task-submissions/'.$submissions[1]->id.'/review', [
                'decision' => 'approved',
                'rating' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('data.task.status', Task::STATUS_CLOSED);

        $this->assertSame(Task::STATUS_CLOSED, $task->refresh()->status);
    }
}
