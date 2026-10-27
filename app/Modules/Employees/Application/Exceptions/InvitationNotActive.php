<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationNotActive extends ApiException
{
    public function __construct(string $message = 'Tidak ada undangan aktif untuk dikirim ulang.')
    {
        parent::__construct($message, 422, [
            'invitation' => [$message],
            'code' => ['INVITATION_NOT_ACTIVE'],
        ]);
    }
}
