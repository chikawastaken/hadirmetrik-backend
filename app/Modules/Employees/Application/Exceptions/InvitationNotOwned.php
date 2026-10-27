<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationNotOwned extends ApiException
{
    public function __construct(string $message = 'Undangan ini bukan milik akun Anda.')
    {
        parent::__construct($message, 403, [
            'code' => ['INVITATION_NOT_OWNED'],
        ]);
    }
}
