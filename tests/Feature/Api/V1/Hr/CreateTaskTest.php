<?php

namespace Tests\Feature\Api\V1\Hr;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class CreateTaskTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_hr_creates_task_with_multiple_assignees(): void
    {
        $hr = $this->makeHrUser();
        $division = $this->makeDivision();
        $first = $this->makeEmployeeRecord(['division_id' => $division->id]);
        $second = $this->makeEmployeeRecord(['division_id' => $division->id]);

        $response = $this->actingAs($hr, 'sanctum')->postJson('/api/v1/hr/tasks', [
            'title' => 'Hitung stok gudang',
            'description' => 'Hitung ulang stok kardus dan lakban.',
            'priority' => 'high',
            'division_id' => $division->id,
            'assignee_ids' => [$first->id, $second->id],
            'requires_photo_proof' => true,
            'checklist_template' => [
                ['key' => 'c1', 'label' => 'Hitung kardus'],
                ['key' => 'c2', 'label' => 'Foto rak'],
            ],
            'due_at' => '2026-11-10T17:00:00+07:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.task.status', 'assigned')
            ->assertJsonPath('data.task.title', 'Hitung stok gudang')
            ->assertJsonCount(2, 'data.task.assignments')
            ->assertJsonPath('data.task.assignments.0.status', 'assigned');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Hitung stok gudang',
            'status' => 'assigned',
            'requires_photo_proof' => true,
        ]);
        $this->assertDatabaseCount('task_assignments', 2);
    }

    public function test_task_creation_rejects_inactive_or_unknown_assignees(): void
    {
        $hr = $this->makeHrUser();
        $active = $this->makeEmployeeRecord();
        $inactive = $this->makeEmployeeRecord(['employment_status' => 'terminated']);

        $payload = [
            'title' => 'Tugas tidak valid',
            'priority' => 'medium',
            'assignee_ids' => [$active->id, $inactive->id],
        ];

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/tasks', $payload)
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $payload['assignee_ids'] = [$active->id, 999999];

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/tasks', $payload)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_employee_cannot_create_tasks(): void
    {
        $employee = $this->makeEmployeeUser();
        $assignee = $this->makeEmployeeRecord();

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/hr/tasks', [
                'title' => 'Tidak boleh',
                'priority' => 'low',
                'assignee_ids' => [$assignee->id],
            ])
            ->assertStatus(403);
    }
}
