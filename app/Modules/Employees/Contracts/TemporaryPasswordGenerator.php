<?php

namespace App\Modules\Employees\Contracts;

interface TemporaryPasswordGenerator
{
    public function generate(int $length = 16): string;
}
