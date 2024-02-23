<?php declare(strict_types=1);

namespace Kirameki\Database\Transaction;

use Throwable;

class SavepointRollback extends Rollback
{
    public readonly string $id;

    public function __construct(string $id, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $this->id = $id;
        parent::__construct($message, $code, $previous);
    }
}
