<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationExpired extends ApiException
{
    public function __construct(string $message = 'Undangan sudah kedaluwarsa.')
    {
        parent::__construct($message, 422, [
            'invitation' => [$message],
            'code' => ['INVITATION_EXPIRED'],
        ]);
    }
}
