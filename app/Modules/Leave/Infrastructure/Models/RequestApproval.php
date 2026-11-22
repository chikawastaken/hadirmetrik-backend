<?php

namespace App\Modules\Leave\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'administrative_request_id',
    'round',
    'step_order',
    'approver_role',
    'approver_user_id',
    'status',
    'note',
    'decided_at',
])]
class RequestApproval extends Model
{
    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_HR = 'hr';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_NEEDS_REVISION = 'needs_revision';

    public const STATUS_SKIPPED = 'skipped';

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'step_order' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRequest::class, 'administrative_request_id');
    }
}
