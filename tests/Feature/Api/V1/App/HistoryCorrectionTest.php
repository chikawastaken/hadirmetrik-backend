<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Attendance\Infrastructure\Models\ClockRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class HistoryCorrectionTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    private function recordFor(int $userId): ClockRecord
    {
        return ClockRecord::create(['user_id' => $userId, 'date' => now()->toDateString(), 'clock_in' => now(), 'status' => 'synced']);
    }

    public function test_owner_can_request_correction_with_reason(): void
    {
        $user = $this->makeEmployeeUser();
        $record = $this->recordFor($user->id);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/history/{$record->id}/correction", ['reason' => 'Lupa absen keluar'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review');

        $this->assertDatabaseHas('clock_records', ['id' => $record->id, 'correction_reason' => 'Lupa absen keluar']);
    }

    public function test_reason_is_required(): void
    {
        $user = $this->makeEmployeeUser();
        $record = $this->recordFor($user->id);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/history/{$record->id}/correction", [])
            ->assertUnprocessable();
    }

    public function test_cannot_correct_another_users_record(): void
    {
        $owner = $this->makeEmployeeUser();
        $record = $this->recordFor($owner->id);

        $this->actingAs($this->makeEmployeeUser(), 'sanctum')
            ->patchJson("/api/v1/history/{$record->id}/correction", ['reason' => 'Bukan punya saya'])
            ->assertNotFound();

        $this->assertDatabaseHas('clock_records', ['id' => $record->id, 'status' => 'synced']);
    }

    public function test_hr_list_is_hr_only_and_schedule_returns_own_shift(): void
    {
        $this->actingAs($this->makeEmployeeUser(), 'sanctum')
            ->getJson('/api/v1/hr/employees')
            ->assertForbidden();

        $this->actingAs($this->makeHrUser(), 'sanctum')
            ->getJson('/api/v1/hr/employees')
            ->assertOk();

        $this->actingAs($this->makeEmployeeUser(), 'sanctum')
            ->getJson('/api/v1/schedule')
            ->assertOk()
            ->assertJsonPath('data.shift', null);
    }
}
