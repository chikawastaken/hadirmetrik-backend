<?php

namespace App\Shared\Contracts;

/**
 * Runs a unit of work atomically. Keeps use cases free of database details.
 */
interface TransactionManager
{
    public function run(callable $callback): mixed;
}
