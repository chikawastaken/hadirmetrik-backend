<?php

namespace App\Modules\Leave\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'request_type',
    'category',
    'special_reason',
    'start_date',
    'end_date',
    'day_portion',
    'day_count',
    'overtime_date',
    'overtime_hours',
    'reason',
    'status',
    'submitted_at',
    'decided_at',
    'cancelled_at',
])]
class AdministrativeRequest extends Model
{
    public const TYPE_CUTI = 'cuti';

    public const TYPE_IZIN = 'izin';

    public const TYPE_LEMBUR = 'lembur';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_NEEDS_REVISION = 'needs_revision';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'overtime_date' => 'date',
            'day_count' => 'float',
            'overtime_hours' => 'float',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RequestApproval::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW], true);
    }
}
