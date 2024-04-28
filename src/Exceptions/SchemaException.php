<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Database\Schema\Statements\SchemaStatement;
use Throwable;

class SchemaException extends SqlException
{
    public function __construct(
        string $message,
        public readonly SchemaStatement $statement,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, ['statement' => $statement], previous: $previous);
    }
}
