<?php declare(strict_types=1);

namespace Kirameki\Database\Exceptions;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Statement;
use Throwable;

class SqlException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly Statement $statement,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, [], 0, $previous);
    }

    /**
     * @inheritDoc
     */
    public function getContext(): array
    {
        return parent::getContext() + [
            'statement' => $this->statement,
        ];
    }
}
