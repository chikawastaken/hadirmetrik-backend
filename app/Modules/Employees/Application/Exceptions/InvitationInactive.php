<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationInactive extends ApiException
{
    public function __construct(string $message = 'Undangan sudah tidak berlaku.')
    {
        parent::__construct($message, 422, [
            'invitation' => [$message],
            'code' => ['INVITATION_INACTIVE'],
        ]);
    }
}
