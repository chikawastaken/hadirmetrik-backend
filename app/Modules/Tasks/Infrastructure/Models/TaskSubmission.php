<?php

namespace App\Modules\Tasks\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'task_assignment_id',
    'note',
    'checklist',
    'review_status',
    'review_rating',
    'review_note',
    'reviewed_by',
    'submitted_at',
    'reviewed_at',
])]
class TaskSubmission extends Model
{
    public const REVIEW_PENDING = 'pending';
    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_REVISION_REQUESTED = 'revision_requested';

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'review_rating' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'task_assignment_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }
}
