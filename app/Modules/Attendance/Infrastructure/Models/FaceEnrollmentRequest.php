<?php

namespace App\Modules\Attendance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class FaceEnrollmentRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    protected $table = 'face_enrollment_requests';

    protected $fillable = [
        'user_id',
        'face_template_id',
        'status',
        'provider',
        'sample_count',
        'rejection_reason',
        'metadata',
        'submitted_at',
        'processed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
