<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Tasks\Infrastructure\Models\Task;
use App\Modules\Tasks\Infrastructure\Models\TaskAttachment;
use App\Modules\Tasks\Infrastructure\Models\TaskSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class TaskAttachmentTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

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

    public function test_assignee_uploads_private_brief_attachment(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/v1/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->image('brief.jpg'),
                'kind' => 'brief',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attachment.kind', 'brief')
            ->assertJsonPath('data.attachment.original_name', 'brief.jpg');

        $attachment = TaskAttachment::query()->firstOrFail();
        $this->assertSame('local', $attachment->storage_disk);
        Storage::disk('local')->assertExists($attachment->object_key);
        $this->assertStringStartsWith('tasks/'.$task->id, $attachment->object_key);
    }

    public function test_evidence_can_be_added_to_an_existing_submission(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
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

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->image('bukti.png'),
                'kind' => 'evidence',
                'submission_id' => $submission->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.attachment.task_submission_id', $submission->id);
    }

    public function test_attachment_rejects_disallowed_mime_type(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->create('script.exe', 10),
                'kind' => 'brief',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('task_attachments', 0);
    }

    public function test_attachment_rejects_files_over_ten_megabytes(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->create('besar.pdf', 11 * 1024),
                'kind' => 'brief',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_evidence_requires_a_submission_id(): void
    {
        $user = $this->makeEmployeeUser();
        $employee = $this->makeEmployeeRecord(['user_id' => $user->id]);
        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->image('bukti.jpg'),
                'kind' => 'evidence',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('submission_id');
    }

    public function test_co_assignee_cannot_upload_evidence_to_another_submission(): void
    {
        $userA = $this->makeEmployeeUser();
        $employeeA = $this->makeEmployeeRecord(['user_id' => $userA->id]);

        $userB = $this->makeEmployeeUser();
        $employeeB = $this->makeEmployeeRecord(['user_id' => $userB->id]);

        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employeeA->id,
            'status' => 'submitted',
        ]);
        $assignmentB = $task->assignments()->create([
            'employee_id' => $employeeB->id,
            'status' => 'submitted',
        ]);
        $submissionB = TaskSubmission::create([
            'task_assignment_id' => $assignmentB->id,
            'checklist' => [],
            'review_status' => TaskSubmission::REVIEW_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($userA, 'sanctum')
            ->post('/api/v1/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->image('curian.jpg'),
                'kind' => 'evidence',
                'submission_id' => $submissionB->id,
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('task_attachments', 0);
    }

    public function test_attachment_download_respects_assignment_ownership(): void
    {
        $userA = $this->makeEmployeeUser();
        $employeeA = $this->makeEmployeeRecord(['user_id' => $userA->id]);

        $userB = $this->makeEmployeeUser();
        $employeeB = $this->makeEmployeeRecord(['user_id' => $userB->id]);

        $task = $this->makeTask();
        $task->assignments()->create([
            'employee_id' => $employeeA->id,
            'status' => 'submitted',
        ]);
        $assignmentB = $task->assignments()->create([
            'employee_id' => $employeeB->id,
            'status' => 'submitted',
        ]);
        $submissionB = TaskSubmission::create([
            'task_assignment_id' => $assignmentB->id,
            'checklist' => [],
            'review_status' => TaskSubmission::REVIEW_PENDING,
            'submitted_at' => now(),
        ]);

        Storage::disk('local')->put('tasks/'.$task->id.'/b.jpg', 'evidence');
        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'task_submission_id' => $submissionB->id,
            'kind' => 'evidence',
            'storage_disk' => 'local',
            'object_key' => 'tasks/'.$task->id.'/b.jpg',
            'original_name' => 'b.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 8,
            'sha256' => str_repeat('b', 64),
            'uploaded_by' => $userB->id,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->get('/api/v1/tasks/'.$task->id.'/attachments/'.$attachment->id)
            ->assertStatus(403);

        $this->actingAs($userB, 'sanctum')
            ->get('/api/v1/tasks/'.$task->id.'/attachments/'.$attachment->id)
            ->assertOk();
    }

    public function test_unrelated_employee_cannot_upload(): void
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
            ->post('/api/v1/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->image('brief.jpg'),
                'kind' => 'brief',
            ])
            ->assertStatus(404);
    }
}
