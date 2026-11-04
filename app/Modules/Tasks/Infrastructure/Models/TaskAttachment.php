<?php

namespace App\Modules\Tasks\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'task_submission_id',
    'kind',
    'storage_disk',
    'object_key',
    'original_name',
    'mime_type',
    'size_bytes',
    'sha256',
    'uploaded_by',
])]
class TaskAttachment extends Model
{
    public const KIND_BRIEF = 'brief';
    public const KIND_EVIDENCE = 'evidence';

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(TaskSubmission::class, 'task_submission_id');
    }
}
