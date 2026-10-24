<?php

namespace App\Shared\Exceptions;

use RuntimeException;

/**
 * Base class for domain failures that map to a predictable HTTP error.
 *
 * Implementations live next to the use case that throws them so the delivery
 * layer never has to translate business rules by hand.
 */
abstract class ApiException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
