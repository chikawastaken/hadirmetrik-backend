<?php

namespace App\Modules\Notification\Infrastructure\Models;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'category',
    'title',
    'body',
    'data',
    'read_at',
    'push_status',
    'created_at',
    'updated_at',
])]
class Notification extends Model
{
    public const CAT_ABSENCE = 'absensi';

    public const CAT_TASK = 'tugas';

    public const CAT_SALARY = 'gaji';

    public const CAT_APPLICATION = 'pengajuan';

    public const CAT_SECURITY = 'keamanan';

    public const CAT_INFO = 'info';

    public const CAT_REMINDER = 'pengingat';

    public const CAT_REVISION = 'revisi';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
