<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Attendance\Infrastructure\Models\AttendanceSyncBatch;
use App\Modules\Attendance\Infrastructure\Models\AttendanceSyncEvent;
use App\Modules\Attendance\Infrastructure\Models\ClockRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class AttendanceSyncTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => 'batch-001',
            'device_id' => 'device-1',
            'events' => [
                [
                    'client_event_id' => 'evt-in-1',
                    'type' => 'clock_in',
                    'occurred_at' => '2026-11-01T08:00:00+07:00',
                    'latitude' => -7.768123,
                    'longitude' => 110.456789,
                ],
                [
                    'client_event_id' => 'evt-out-1',
                    'type' => 'clock_out',
                    'occurred_at' => '2026-11-01T17:00:00+07:00',
                    'latitude' => -7.768123,
                    'longitude' => 110.456789,
                ],
            ],
        ], $overrides);
    }

    public function test_batch_sync_creates_clock_in_and_out(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total_events', 2)
            ->assertJsonPath('data.processed_events', 2)
            ->assertJsonPath('data.failed_events', 0)
            ->assertJsonPath('data.events.0.status', 'synced')
            ->assertJsonPath('data.events.1.status', 'synced');

        $record = ClockRecord::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('2026-11-01', $record->date);
        $this->assertSame('offline', $record->source);
        $this->assertNotNull($record->clock_in);
        $this->assertNotNull($record->clock_out);
        $this->assertNotNull($record->synced_at);
    }

    public function test_replaying_same_batch_is_idempotent(): void
    {
        $user = $this->makeEmployeeUser();
        $payload = $this->payload();

        $first = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', $payload)
            ->assertStatus(201);

        $second = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, ClockRecord::where('user_id', $user->id)->count());
        $this->assertSame(2, AttendanceSyncEvent::where('user_id', $user->id)->count());
        $this->assertSame(1, AttendanceSyncBatch::where('user_id', $user->id)->count());
        $this->assertNotNull($second->json('data.id'));
    }

    public function test_duplicate_clock_in_is_rejected(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', $this->payload())
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', $this->payload([
                'idempotency_key' => 'batch-002',
                'events' => [
                    [
                        'client_event_id' => 'evt-in-2',
                        'type' => 'clock_in',
                        'occurred_at' => '2026-11-01T09:00:00+07:00',
                    ],
                ],
            ]))
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.processed_events', 0)
            ->assertJsonPath('data.failed_events', 1)
            ->assertJsonPath('data.events.0.status', 'rejected');

        $this->assertSame(1, ClockRecord::where('user_id', $user->id)->count());
    }

    public function test_clock_out_without_clock_in_is_rejected(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', $this->payload([
                'idempotency_key' => 'batch-003',
                'events' => [
                    [
                        'client_event_id' => 'evt-out-only',
                        'type' => 'clock_out',
                        'occurred_at' => '2026-11-01T17:00:00+07:00',
                    ],
                ],
            ]))
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.events.0.status', 'rejected');

        $this->assertSame(0, ClockRecord::where('user_id', $user->id)->count());
    }

    public function test_show_batch_returns_events(): void
    {
        $user = $this->makeEmployeeUser();

        $created = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', $this->payload())
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sync/attendance/'.$created->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.idempotency_key', 'batch-001')
            ->assertJsonCount(2, 'data.events');
    }

    public function test_validation_requires_events(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sync/attendance', ['idempotency_key' => 'batch-x'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_sync_requires_auth(): void
    {
        $this->postJson('/api/v1/sync/attendance', $this->payload())->assertStatus(401);
    }
}
