<?php

namespace App\Modules\Tasks;

use App\Modules\Tasks\Application\Support\TaskAccess;
use Illuminate\Support\ServiceProvider;

class TasksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaskAccess::class, TaskAccess::class);
    }
}
