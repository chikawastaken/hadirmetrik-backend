<?php

namespace App\Modules\Employees\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class InvitationPhoneRequired extends ApiException
{
    public function __construct(string $message = 'Nomor telepon wajib diisi untuk kanal WhatsApp.')
    {
        parent::__construct($message, 422, [
            'phone' => [$message],
            'code' => ['PHONE_REQUIRED'],
        ]);
    }
}
