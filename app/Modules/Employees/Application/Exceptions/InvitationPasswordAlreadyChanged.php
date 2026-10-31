<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationPasswordAlreadyChanged extends ApiException
{
    public function __construct(string $message = 'Kata sandi akun ini sudah pernah diganti. Silakan masuk dengan kata sandi Anda.')
    {
        parent::__construct($message, 409, [
            'code' => ['INVITATION_PASSWORD_ALREADY_CHANGED'],
        ]);
    }
}
