<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\InvitationDeliveryDispatcher;
use App\Modules\Employees\Infrastructure\Jobs\SendInvitationDelivery;

final class QueueInvitationDeliveryDispatcher implements InvitationDeliveryDispatcher
{
    public function dispatch(int $deliveryId, string $plainToken): void
    {
        SendInvitationDelivery::dispatch($deliveryId, $plainToken)->afterCommit();
    }
}
