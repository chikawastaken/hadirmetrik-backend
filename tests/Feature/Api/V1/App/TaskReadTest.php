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

    public function test_co_assignee_only_sees_their_own_assignment_and_evidence(): void
    {
        $hr = $this->makeHrUser();
        $task = $this->makeTask(['created_by' => $hr->id]);

        $userA = $this->makeEmployeeUser();
        $employeeA = $this->makeEmployeeRecord(['user_id' => $userA->id]);
        $assignmentA = $task->assignments()->create([
            'employee_id' => $employeeA->id,
            'status' => 'assigned',
        ]);

        $userB = $this->makeEmployeeUser();
        $employeeB = $this->makeEmployeeRecord(['user_id' => $userB->id]);
        $assignmentB = $task->assignments()->create([
            'employee_id' => $employeeB->id,
            'status' => 'submitted',
        ]);

        $submissionB = \App\Modules\Tasks\Infrastructure\Models\TaskSubmission::create([
            'task_assignment_id' => $assignmentB->id,
            'checklist' => [],
            'review_status' => 'pending',
            'submitted_at' => now(),
        ]);

        \App\Modules\Tasks\Infrastructure\Models\TaskAttachment::create([
            'task_id' => $task->id,
            'task_submission_id' => $submissionB->id,
            'kind' => 'evidence',
            'storage_disk' => 'local',
            'object_key' => 'tasks/'.$task->id.'/b-evidence.jpg',
            'original_name' => 'b-evidence.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'sha256' => str_repeat('b', 64),
            'uploaded_by' => $userB->id,
        ]);

        \App\Modules\Tasks\Infrastructure\Models\TaskAttachment::create([
            'task_id' => $task->id,
            'kind' => 'brief',
            'storage_disk' => 'local',
            'object_key' => 'tasks/'.$task->id.'/brief.pdf',
            'original_name' => 'brief.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'sha256' => str_repeat('c', 64),
            'uploaded_by' => $hr->id,
        ]);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/tasks/'.$task->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data.task.assignments')
            ->assertJsonPath('data.task.assignments.0.id', $assignmentA->id)
            ->assertJsonCount(0, 'data.task.submissions')
            ->assertJsonCount(1, 'data.task.attachments')
            ->assertJsonPath('data.task.attachments.0.kind', 'brief');
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
