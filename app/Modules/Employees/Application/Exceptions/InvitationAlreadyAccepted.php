<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationAlreadyAccepted extends ApiException
{
    public function __construct(string $message = 'Undangan sudah diterima dan tidak dapat dipakai lagi.')
    {
        parent::__construct($message, 422, [
            'invitation' => [$message],
            'code' => ['INVITATION_ALREADY_ACCEPTED'],
        ]);
    }
}
