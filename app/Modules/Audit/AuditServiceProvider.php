<?php

namespace App\Modules\Audit;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Audit\Infrastructure\Services\DatabaseSecurityEventRecorder;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SecurityEventRecorder::class, DatabaseSecurityEventRecorder::class);
    }
}
