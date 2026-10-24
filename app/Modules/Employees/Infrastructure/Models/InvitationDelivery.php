<?php

namespace App\Modules\Employees\Infrastructure\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invitation_id',
    'channel',
    'recipient',
    'provider',
    'provider_message_id',
    'status',
    'attempt',
    'error_code',
    'error_message',
    'sent_at',
    'delivered_at',
    'failed_at',
])]
class InvitationDelivery extends Model
{
    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(EmployeeInvitation::class, 'invitation_id');
    }
}
