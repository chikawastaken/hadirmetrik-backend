<?php

namespace App\Modules\Leave\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class RequestStateConflict extends ApiException
{
    public function __construct(string $message = 'Status pengajuan tidak dapat diubah saat ini.', string $code = 'STATE_NOT_TRANSITIONABLE')
    {
        parent::__construct($message, 409, ['code' => [$code]]);
    }
}
