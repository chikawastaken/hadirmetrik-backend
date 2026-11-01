<?php

namespace App\Modules\Attendance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSyncBatch extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $table = 'attendance_sync_batches';

    protected $fillable = [
        'user_id',
        'idempotency_key',
        'device_id',
        'status',
        'total_events',
        'processed_events',
        'failed_events',
        'payload_hash',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * @return HasMany<AttendanceSyncEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(AttendanceSyncEvent::class, 'batch_id');
    }
}
