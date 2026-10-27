<?php

namespace App\Modules\Identity\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class PasswordChangeRequired extends ApiException
{
    public function __construct(string $message = 'Anda harus mengganti kata sandi sebelum mengakses fitur ini.')
    {
        parent::__construct($message, 403, [
            'code' => ['PASSWORD_CHANGE_REQUIRED'],
        ]);
    }
}
