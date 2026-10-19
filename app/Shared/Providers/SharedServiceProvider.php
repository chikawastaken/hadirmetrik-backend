<?php

namespace App\Shared\Providers;

use App\Shared\Contracts\TransactionManager;
use App\Shared\Infrastructure\DatabaseTransactionManager;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransactionManager::class, DatabaseTransactionManager::class);
    }
}
