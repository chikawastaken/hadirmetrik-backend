<?php

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvalidCurrentPassword extends ApiException
{
    public function __construct(string $message = 'Kata sandi saat ini tidak sesuai.')
    {
        parent::__construct($message, 422, [
            'current_password' => [$message],
        ]);
    }
}
