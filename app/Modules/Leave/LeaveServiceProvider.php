<?php

namespace App\Modules\Leave;

use App\Modules\Leave\Application\Support\LeaveAccess;
use Illuminate\Support\ServiceProvider;

class LeaveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeaveAccess::class, LeaveAccess::class);
    }
}
