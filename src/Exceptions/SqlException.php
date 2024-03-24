<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Statement;
use Throwable;
use function dump;

class SqlException extends RuntimeException
{
    public function __construct(
        string $message,
        Statement $statement,
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, [
            'statement' => $statement,
        ], $code, $previous);
    }
}
