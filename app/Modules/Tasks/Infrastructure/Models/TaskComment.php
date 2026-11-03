<?php

namespace App\Modules\Tasks\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'author_user_id',
    'body',
])]
class TaskComment extends Model
{
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
