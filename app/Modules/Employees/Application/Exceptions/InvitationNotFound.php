<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationNotFound extends ApiException
{
    public function __construct(string $message = 'Undangan tidak ditemukan.')
    {
        parent::__construct($message, 404, [
            'code' => ['INVITATION_NOT_FOUND'],
        ]);
    }
}
