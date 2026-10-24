<?php

namespace App\Modules\Audit\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event', 'actor_user_id', 'context'])]
class SecurityEvent extends Model
{
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }
}
