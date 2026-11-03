<?php

namespace App\Modules\Tasks\Application\Exceptions;

use App\Shared\Exceptions\ApiException;

final class EvidenceRequired extends ApiException
{
    public function __construct(string $message = 'Tugas ini membutuhkan bukti foto.')
    {
        parent::__construct($message, 422, ['code' => ['EVIDENCE_REQUIRED']]);
    }
}
