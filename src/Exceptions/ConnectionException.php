<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Database\Config\ConnectionConfig;
use Throwable;

class ConnectionException extends DatabaseException
{
    /**
     * @param string $message
     * @param ConnectionConfig $config
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message,
        ConnectionConfig $config,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, ['connectionConfig' => $config], previous: $previous);
    }
}
