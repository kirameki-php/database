<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Database\Query\Statements\QueryStatement;
use Throwable;

class QueryException extends SqlException
{
    /**
     * @param string $message
     * @param QueryStatement $statement
     * @param iterable<string, mixed>|null $context
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message,
        public readonly QueryStatement $statement,
        ?iterable $context = null,
        ?Throwable $previous = null,
    )
    {
        parent::__construct(
            $message,
            ($context ?? []) + ['statement' => $statement],
            0,
            $previous,
        );
    }
}
