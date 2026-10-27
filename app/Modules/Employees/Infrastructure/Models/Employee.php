<?php

namespace App\Modules\Employees\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'employee_number',
    'full_name',
    'work_email',
    'phone_e164',
    'division_id',
    'job_title',
    'manager_id',
    'employment_start',
    'employment_status',
    'face_registered_at',
])]
class Employee extends Model
{
    protected function casts(): array
    {
        return [
            'employment_start' => 'date',
            'face_registered_at' => 'datetime',
        ];
    }

    public function getNameAttribute(): ?string
    {
        return $this->full_name;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['full_name'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->employment_status !== 'terminated';
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
