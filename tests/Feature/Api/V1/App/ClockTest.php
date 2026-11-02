<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Attendance\Infrastructure\Models\ClockRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class ClockTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_clock_in_creates_record(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/clock-in')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending_sync');

        $this->assertDatabaseHas('clock_records', [
            'user_id' => $user->id,
            'status' => 'pending_sync',
        ]);
    }

    public function test_clock_in_twice_is_rejected(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/clock-in')->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/clock-in')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(1, ClockRecord::where('user_id', $user->id)->count());
    }

    public function test_clock_out_requires_clock_in(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/clock-out')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_clock_out_updates_record(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/clock-in')->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/clock-out')
            ->assertOk()
            ->assertJsonPath('data.status', 'synced');

        $record = ClockRecord::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($record->clock_out);
    }

    public function test_history_and_schedule_endpoints_work(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/history')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/schedule')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/clock')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_schedule_returns_active_shifts(): void
    {
        $user = $this->makeEmployeeUser();
        \App\Modules\Employees\Infrastructure\Models\Shift::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/schedule')
            ->assertOk()
            ->assertJsonPath('data.shifts.0.name', 'Shift Pagi')
            ->assertJsonPath('data.shifts.0.start_time', '08:00:00');
    }

    public function test_clock_routes_require_auth(): void
    {
        $this->postJson('/api/v1/clock-in')->assertStatus(401);
    }
}
