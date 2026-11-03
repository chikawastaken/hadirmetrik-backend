<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Tasks\Infrastructure\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class TaskCommentTest extends TestCase
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

    public function test_assignee_and_creator_can_comment(): void
    {
        $hr = $this->makeHrUser();
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask(['created_by' => $hr->id]);
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', [
                'body' => 'Saya mulai besok pagi.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.comment.author_user_id', $user->id)
            ->assertJsonPath('data.comment.body', 'Saya mulai besok pagi.')
            ->assertJsonPath('data.comment.author_name', $user->name);

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', [
                'body' => 'Baik, ditunggu.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.comment.author_user_id', $hr->id);

        $this->assertDatabaseCount('task_comments', 2);
    }

    public function test_unrelated_employee_cannot_comment(): void
    {
        $user = $this->makeEmployeeUser();
        $this->makeEmployeeRecord(['user_id' => $user->id]);

        $other = $this->makeEmployeeRecord();
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $other->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', ['body' => 'Halo'])
            ->assertStatus(404);

        $this->assertDatabaseCount('task_comments', 0);
    }

    public function test_comment_body_is_required_and_limited_to_2000(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks/'.$task->id.'/comments', ['body' => str_repeat('a', 2001)])
            ->assertStatus(422);
    }
}
