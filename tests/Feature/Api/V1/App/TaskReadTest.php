<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Tasks\Infrastructure\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class TaskReadTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    private function makeTask(array $attributes = []): Task
    {
        return Task::create(array_merge([
            'created_by' => $this->makeHrUser()->id,
            'title' => 'Tugas '.uniqid(),
            'description' => 'Deskripsi tugas',
            'priority' => 'medium',
            'status' => Task::STATUS_ASSIGNED,
            'requires_photo_proof' => false,
            'checklist_template' => [],
        ], $attributes));
    }

    public function test_employee_sees_only_own_assignments(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);

        $otherUser = $this->makeEmployeeUser();
        $otherEmployee = $this->makeEmployeeRecord(['user_id' => $otherUser->id]);

        $ownTask = $this->makeTask(['title' => 'Tugas saya']);
        $ownTask->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $otherTask = $this->makeTask(['title' => 'Tugas orang lain']);
        $otherTask->assignments()->create([
            'employee_id' => $otherEmployee->id,
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.tasks')
            ->assertJsonPath('data.tasks.0.task.id', $ownTask->id)
            ->assertJsonPath('data.tasks.0.assignment.employee_id', $employee->id);
    }

    public function test_task_detail_hides_another_employees_assignment(): void
    {
        $user = $this->makeEmployeeUser();
        $this->makeEmployeeRecord(['user_id' => $user->id]);

        $otherUser = $this->makeEmployeeUser();
        $otherEmployee = $this->makeEmployeeRecord(['user_id' => $otherUser->id]);

        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $otherEmployee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks/'.$task->id)
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_hr_can_view_task_it_created(): void
    {
        $hr = $this->makeHrUser();
        $employee = $this->makeEmployeeRecord();

        $task = $this->makeTask(['created_by' => $hr->id, 'title' => 'Tugas HR']);
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($hr, 'sanctum')
            ->getJson('/api/v1/tasks/'.$task->id)
            ->assertOk()
            ->assertJsonPath('data.task.id', $task->id)
            ->assertJsonPath('data.task.title', 'Tugas HR')
            ->assertJsonCount(1, 'data.task.assignments');
    }
}
