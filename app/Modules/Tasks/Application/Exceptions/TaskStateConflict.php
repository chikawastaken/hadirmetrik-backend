<?php

namespace App\Modules\Tasks\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class TaskStateConflict extends ApiException
{
    public function __construct(string $message = 'Status tugas tidak dapat diubah saat ini.', string $code = 'STATE_NOT_TRANSITIONABLE')
    {
        parent::__construct($message, 409, ['code' => [$code]]);
    }
}
