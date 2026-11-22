<?php

namespace App\Modules\Leave\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class RequestForbidden extends ApiException
{
    public function __construct(string $message = 'Kamu tidak berhak memproses pengajuan ini.')
    {
        parent::__construct($message, 403);
    }
}
