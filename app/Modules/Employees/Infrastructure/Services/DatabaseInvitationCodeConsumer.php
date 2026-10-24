<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\InvitationCodeConsumer;
use App\Modules\Employees\Infrastructure\Models\InvitationCode;

final class DatabaseInvitationCodeConsumer implements InvitationCodeConsumer
{
    public function isConsumable(string $code): bool
    {
        return InvitationCode::query()
            ->where('code', $code)
            ->where('is_used', false)
            ->exists();
    }

    public function consume(string $code): bool
    {
        // The "is_used = false" guard makes the update atomic, so two concurrent
        // registrations cannot both consume the same code.
        return InvitationCode::query()
            ->where('code', $code)
            ->where('is_used', false)
            ->update(['is_used' => true, 'used_at' => now()]) === 1;
    }
}
