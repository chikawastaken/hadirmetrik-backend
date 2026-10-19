<?php

namespace App\Modules\Employees\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'created_by', 'is_used', 'used_at'])]
class InvitationCode extends Model
{
    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }
}
