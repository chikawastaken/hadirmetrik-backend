<?php

namespace Tests\Feature\Api\V1\Hr;

use App\Modules\Tasks\Infrastructure\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class CancelTaskTest extends TestCase
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

    public function test_creator_can_cancel_assigned_task(): void
    {
        $hr = $this->makeHrUser();
        $employee = $this->makeEmployeeRecord();
        $task = $this->makeTask(['created_by' => $hr->id]);
        $assignment = $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/tasks/'.$task->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.task.status', Task::STATUS_CANCELLED);

        $this->assertNotNull($task->refresh()->cancelled_at);
        $this->assertSame('cancelled', $assignment->refresh()->status);
    }

    public function test_cancelled_task_rejects_start_progress_and_submit(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['status' => Task::STATUS_CANCELLED, 'cancelled_at' => now()]);
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        foreach ([
            ['post', '/api/v1/tasks/'.$task->id.'/start', []],
            ['patch', '/api/v1/tasks/'.$task->id.'/progress', ['progress_percent' => 50]],
            ['post', '/api/v1/tasks/'.$task->id.'/submissions', []],
        ] as [$method, $url, $payload]) {
            $this->actingAs($user, 'sanctum')
                ->{$method.'Json'}($url, $payload)
                ->assertStatus(409)
                ->assertJsonPath('errors.code.0', 'STATE_NOT_TRANSITIONABLE');
        }
    }

    public function test_hr_cannot_cancel_another_hrs_task(): void
    {
        $creator = $this->makeHrUser();
        $otherHr = $this->makeHrUser();
        $task = $this->makeTask(['created_by' => $creator->id]);

        $this->actingAs($otherHr, 'sanctum')
            ->postJson('/api/v1/hr/tasks/'.$task->id.'/cancel')
            ->assertStatus(403);

        $this->assertSame(Task::STATUS_ASSIGNED, $task->refresh()->status);
    }

    public function test_admin_can_cancel_another_hrs_task(): void
    {
        $creator = $this->makeHrUser();
        $admin = $this->makeHrUser(['role' => 'admin']);
        $task = $this->makeTask(['created_by' => $creator->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/hr/tasks/'.$task->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.task.status', Task::STATUS_CANCELLED);
    }

    public function test_employee_cannot_cancel_task(): void
    {
        $user = $this->makeEmployeeUser();
        $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/hr/tasks/'.$task->id.'/cancel')
            ->assertStatus(403);
    }

    public function test_closed_task_cannot_be_cancelled(): void
    {
        $hr = $this->makeHrUser();
        $task = $this->makeTask(['created_by' => $hr->id, 'status' => Task::STATUS_CLOSED]);

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/tasks/'.$task->id.'/cancel')
            ->assertStatus(409);
    }
}
