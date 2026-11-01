<?php

namespace App\Modules\Attendance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSyncEvent extends Model
{
    public const TYPE_CLOCK_IN = 'clock_in';

    public const TYPE_CLOCK_OUT = 'clock_out';

    public const STATUS_PENDING_SYNC = 'pending_sync';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SYNC_FAILED = 'sync_failed';

    protected $table = 'attendance_sync_events';

    protected $fillable = [
        'batch_id',
        'user_id',
        'client_event_id',
        'idempotency_key',
        'event_type',
        'occurred_at',
        'latitude',
        'longitude',
        'status',
        'error_message',
        'clock_record_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * @return BelongsTo<AttendanceSyncBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(AttendanceSyncBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<ClockRecord, $this>
     */
    public function clockRecord(): BelongsTo
    {
        return $this->belongsTo(ClockRecord::class, 'clock_record_id');
    }
}
