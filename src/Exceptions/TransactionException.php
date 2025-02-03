<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Throwable;

class TransactionException extends SqlException
{
    /**
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, previous: $previous);
    }
}
