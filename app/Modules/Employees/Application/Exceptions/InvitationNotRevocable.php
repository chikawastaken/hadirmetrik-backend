<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationNotRevocable extends ApiException
{
    public function __construct(string $message = 'Undangan tidak dapat dicabut pada status ini.')
    {
        parent::__construct($message, 422, [
            'invitation' => [$message],
            'code' => ['INVITATION_NOT_REVOCABLE'],
        ]);
    }
}
