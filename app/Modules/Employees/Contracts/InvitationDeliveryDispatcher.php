<?php

namespace App\Modules\Employees\Contracts;

interface InvitationDeliveryDispatcher
{
    public function dispatch(int $deliveryId, string $plainToken): void;
}
