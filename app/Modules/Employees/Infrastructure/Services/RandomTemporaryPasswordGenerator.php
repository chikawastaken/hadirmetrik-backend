<?php

namespace App\Modules\Employees\Infrastructure\Services;

use App\Modules\Employees\Contracts\TemporaryPasswordGenerator;
use Illuminate\Support\Str;

final class RandomTemporaryPasswordGenerator implements TemporaryPasswordGenerator
{
    public function generate(int $length = 16): string
    {
        return Str::password($length);
    }
}
