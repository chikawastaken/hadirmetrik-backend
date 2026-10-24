<?php

namespace App\Modules\Audit\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'actor_user_id',
    'entity_type',
    'entity_id',
    'action',
    'before_snapshot',
    'after_snapshot',
    'reason',
    'ip_address',
    'user_agent',
    'recorded_at',
])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'before_snapshot' => 'array',
            'after_snapshot' => 'array',
            'recorded_at' => 'datetime',
        ];
    }
}
