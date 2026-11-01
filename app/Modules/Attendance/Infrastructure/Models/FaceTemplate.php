<?php

namespace App\Modules\Attendance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class FaceTemplate extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_DELETED = 'deleted';

    protected $table = 'face_templates';

    protected $fillable = [
        'user_id',
        'version',
        'status',
        'provider',
        'template_ref',
        'metadata',
        'enrolled_at',
        'revoked_at',
        'deleted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'enrolled_at' => 'datetime',
        'revoked_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
