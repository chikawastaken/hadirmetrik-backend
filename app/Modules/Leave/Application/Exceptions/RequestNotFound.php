<?php

namespace App\Modules\Leave\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class RequestNotFound extends ApiException
{
    public function __construct(string $message = 'Pengajuan tidak ditemukan.')
    {
        parent::__construct($message, 404);
    }
}
