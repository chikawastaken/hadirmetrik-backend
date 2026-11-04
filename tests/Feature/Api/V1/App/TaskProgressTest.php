<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Tasks\Infrastructure\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class TaskProgressTest extends TestCase
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

    public function test_assignee_starts_task(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/start')
            ->assertOk()
            ->assertJsonPath('data.task.assignments.0.status', 'in_progress');

        $assignment->refresh();
        $this->assertSame('in_progress', $assignment->status);
        $this->assertNotNull($assignment->started_at);
    }

    public function test_starting_twice_returns_conflict(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/start')
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/start')
            ->assertStatus(409)
            ->assertJsonPath('errors.code.0', 'STATE_NOT_TRANSITIONABLE');
    }

    public function test_progress_is_clamped_to_zero_to_one_hundred(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/tasks/'.$task->id.'/progress', [
                'progress_percent' => 150,
                'checklist' => [
                    ['key' => 'c1', 'label' => 'Langkah 1', 'done' => true],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.task.assignments.0.progress_percent', 100);

        $this->assertSame(100, $assignment->refresh()->progress_percent);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/tasks/'.$task->id.'/progress', [
                'progress_percent' => -20,
            ])
            ->assertOk()
            ->assertJsonPath('data.task.assignments.0.progress_percent', 0);
    }

    public function test_progress_persists_checklist_state(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/tasks/'.$task->id.'/progress', [
                'progress_percent' => 40,
                'checklist' => [
                    ['key' => 'c1', 'label' => 'Langkah 1', 'done' => true],
                    ['key' => 'c2', 'label' => 'Langkah 2', 'done' => false],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.task.assignments.0.checklist.0.done', true)
            ->assertJsonPath('data.task.assignments.0.checklist.1.done', false);

        $this->assertSame([
            ['key' => 'c1', 'label' => 'Langkah 1', 'done' => true],
            ['key' => 'c2', 'label' => 'Langkah 2', 'done' => false],
        ], $assignment->refresh()->checklist);
    }

    public function test_non_assignee_cannot_update_progress(): void
    {
        $user = $this->makeEmployeeUser();
        $this->makeEmployeeRecord(['user_id' => $user->id]);

        $otherEmployee = $this->makeEmployeeRecord();
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $otherEmployee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/tasks/'.$task->id.'/progress', ['progress_percent' => 50])
            ->assertStatus(404);
    }

    public function test_cancelled_task_cannot_be_started(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['status' => Task::STATUS_CANCELLED, 'cancelled_at' => now()]);
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/start')
            ->assertStatus(409)
            ->assertJsonPath('errors.code.0', 'STATE_NOT_TRANSITIONABLE');
    }
}
