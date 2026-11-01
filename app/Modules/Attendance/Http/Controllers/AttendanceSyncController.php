<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Http\Requests\StoreAttendanceSyncRequest;
use App\Modules\Attendance\Infrastructure\Models\AttendanceSyncBatch;
use App\Modules\Attendance\Infrastructure\Models\AttendanceSyncEvent;
use App\Modules\Attendance\Infrastructure\Models\ClockRecord;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceSyncController extends Controller
{
    /**
     * Accept a batch of offline attendance events.
     *
     * Replaying the same batch idempotency key returns the original batch.
     * Per-event duplicates are reported as `rejected`, never inserted twice.
     */
    public function store(StoreAttendanceSyncRequest $request): ApiResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $existing = AttendanceSyncBatch::where('user_id', $user->id)
            ->where('idempotency_key', $validated['idempotency_key'])
            ->with('events')
            ->first();

        if ($existing) {
            return ApiResponse::success(
                $this->batchPayload($existing),
                'Batch sudah pernah diterima (idempotent).',
            );
        }

        $batch = DB::transaction(function () use ($user, $validated): AttendanceSyncBatch {
            $batch = AttendanceSyncBatch::create([
                'user_id' => $user->id,
                'idempotency_key' => $validated['idempotency_key'],
                'device_id' => $validated['device_id'] ?? null,
                'status' => AttendanceSyncBatch::STATUS_PROCESSING,
                'total_events' => count($validated['events']),
                'payload_hash' => hash('sha256', (string) json_encode($validated['events'])),
                'received_at' => now(),
            ]);

            $processed = 0;
            $failed = 0;
            $results = collect();

            foreach ($validated['events'] as $input) {
                $eventKey = $input['idempotency_key']
                    ?? ($validated['idempotency_key'].':'.$input['client_event_id']);

                $duplicate = AttendanceSyncEvent::where('user_id', $user->id)
                    ->where(function ($query) use ($input, $eventKey): void {
                        $query->where('client_event_id', $input['client_event_id'])
                            ->orWhere('idempotency_key', $eventKey);
                    })
                    ->first();

                if ($duplicate) {
                    $results->push($duplicate);
                    $processed++;

                    continue;
                }

                $event = AttendanceSyncEvent::create([
                    'batch_id' => $batch->id,
                    'user_id' => $user->id,
                    'client_event_id' => $input['client_event_id'],
                    'idempotency_key' => $eventKey,
                    'event_type' => $input['type'],
                    'occurred_at' => Carbon::parse($input['occurred_at']),
                    'latitude' => $input['latitude'] ?? null,
                    'longitude' => $input['longitude'] ?? null,
                    'status' => AttendanceSyncEvent::STATUS_PENDING_SYNC,
                ]);

                try {
                    $this->applyEvent($event, $user->id, $batch->device_id);
                } catch (\Throwable $exception) {
                    $event->update([
                        'status' => AttendanceSyncEvent::STATUS_SYNC_FAILED,
                        'error_message' => Str::limit($exception->getMessage(), 250),
                    ]);
                }

                $event->refresh();
                $results->push($event);

                if ($event->status === AttendanceSyncEvent::STATUS_SYNCED) {
                    $processed++;
                } else {
                    $failed++;
                }
            }

            $status = match (true) {
                $failed === 0 => AttendanceSyncBatch::STATUS_COMPLETED,
                $processed === 0 => AttendanceSyncBatch::STATUS_FAILED,
                default => AttendanceSyncBatch::STATUS_PARTIAL,
            };

            $batch->update([
                'status' => $status,
                'processed_events' => $processed,
                'failed_events' => $failed,
                'processed_at' => now(),
            ]);

            $batch->setRelation('events', $results);

            return $batch;
        });

        return ApiResponse::success($this->batchPayload($batch), 'Batch presensi offline diproses.', 201);
    }

    public function show(Request $request, int $id): ApiResponse
    {
        $batch = AttendanceSyncBatch::where('user_id', $request->user()->id)
            ->with('events')
            ->find($id);

        if (! $batch) {
            return ApiResponse::error('Batch sync tidak ditemukan.', 404);
        }

        return ApiResponse::success($this->batchPayload($batch));
    }

    private function applyEvent(AttendanceSyncEvent $event, int $userId, ?string $deviceId): void
    {
        $date = $event->occurred_at->toDateString();

        if ($event->event_type === AttendanceSyncEvent::TYPE_CLOCK_IN) {
            $existing = ClockRecord::where('user_id', $userId)
                ->whereDate('date', $date)
                ->first();

            if ($existing && $existing->clock_in !== null) {
                $event->update([
                    'status' => AttendanceSyncEvent::STATUS_REJECTED,
                    'error_message' => 'Absen masuk sudah tercatat pada tanggal tersebut.',
                    'clock_record_id' => $existing->id,
                ]);

                return;
            }

            $record = $existing ?? new ClockRecord([
                'user_id' => $userId,
                'date' => $date,
            ]);

            $record->fill([
                'clock_in' => $event->occurred_at,
                'status' => AttendanceSyncEvent::STATUS_SYNCED,
                'source' => 'offline',
                'device_id' => $deviceId,
                'idempotency_key' => $event->idempotency_key,
                'synced_at' => now(),
            ])->save();

            $event->update([
                'status' => AttendanceSyncEvent::STATUS_SYNCED,
                'clock_record_id' => $record->id,
            ]);

            return;
        }

        $record = ClockRecord::where('user_id', $userId)
            ->whereDate('date', $date)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (! $record) {
            $event->update([
                'status' => AttendanceSyncEvent::STATUS_REJECTED,
                'error_message' => 'Tidak ada absen masuk aktif pada tanggal tersebut.',
            ]);

            return;
        }

        $record->update([
            'clock_out' => $event->occurred_at,
            'status' => AttendanceSyncEvent::STATUS_SYNCED,
            'source' => 'offline',
            'device_id' => $deviceId,
            'synced_at' => now(),
        ]);

        $event->update([
            'status' => AttendanceSyncEvent::STATUS_SYNCED,
            'clock_record_id' => $record->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function batchPayload(AttendanceSyncBatch $batch): array
    {
        /** @var Collection<int, AttendanceSyncEvent> $events */
        $events = $batch->relationLoaded('events')
            ? $batch->events
            : $batch->events()->get();

        return [
            'id' => $batch->id,
            'idempotency_key' => $batch->idempotency_key,
            'status' => $batch->status,
            'device_id' => $batch->device_id,
            'total_events' => $batch->total_events,
            'processed_events' => $batch->processed_events,
            'failed_events' => $batch->failed_events,
            'received_at' => $batch->received_at?->toIso8601String(),
            'processed_at' => $batch->processed_at?->toIso8601String(),
            'events' => $events->map(fn (AttendanceSyncEvent $event) => [
                'id' => $event->id,
                'client_event_id' => $event->client_event_id,
                'type' => $event->event_type,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
                'status' => $event->status,
                'error_message' => $event->error_message,
                'clock_record_id' => $event->clock_record_id,
            ])->values(),
        ];
    }
}
