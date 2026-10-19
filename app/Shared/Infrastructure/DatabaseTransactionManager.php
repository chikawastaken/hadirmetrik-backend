<?php

namespace App\Shared\Infrastructure;

use App\Shared\Contracts\TransactionManager;
use Illuminate\Database\DatabaseManager;

final class DatabaseTransactionManager implements TransactionManager
{
    public function __construct(private readonly DatabaseManager $database) {}

    public function run(callable $callback): mixed
    {
        return $this->database->transaction($callback);
    }
}
