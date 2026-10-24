<?php

namespace App\Modules\Identity\Contracts\Data;

final readonly class IssuedToken
{
    public function __construct(
        public string $token,
        public string $type = 'Bearer',
    ) {}
}
