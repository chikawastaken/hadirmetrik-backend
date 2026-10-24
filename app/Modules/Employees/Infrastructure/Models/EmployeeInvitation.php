<?php

namespace App\Modules\Employees\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'purpose',
    'token_hash',
    'status',
    'requested_channels',
    'expires_at',
    'sent_at',
    'last_sent_at',
    'accepted_at',
    'revoked_at',
    'attempts',
    'created_by',
])]
class EmployeeInvitation extends Model
{
    protected function casts(): array
    {
        return [
            'requested_channels' => 'array',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(InvitationDelivery::class, 'invitation_id');
    }
}
