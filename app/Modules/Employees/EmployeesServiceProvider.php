<?php

namespace App\Modules\Employees;

use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Employees\Contracts\InvitationCodeConsumer;
use App\Modules\Employees\Infrastructure\Services\DatabaseInvitationCodeConsumer;
use App\Modules\Employees\Infrastructure\Services\EloquentEmployeeDirectory;
use Illuminate\Support\ServiceProvider;

class EmployeesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeDirectory::class, EloquentEmployeeDirectory::class);
        $this->app->bind(InvitationCodeConsumer::class, DatabaseInvitationCodeConsumer::class);
    }
}
