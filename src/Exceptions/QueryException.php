<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Database\Query\Statements\QueryStatement;
use Throwable;

class QueryException extends SqlException
{
    public function __construct(
        string $message,
        QueryStatement $statement,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, $statement, $previous);
    }
}
