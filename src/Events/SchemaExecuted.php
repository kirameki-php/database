<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;

class SchemaExecuted extends DatabaseEvent
{
    /**
     * @var string
     */
    public readonly string $statement;

    /**
     * @var float
     */
    public readonly float $elapsedMs;

    /**
     * @param Connection $connection
     * @param string $statement
     * @param float $elapsedMs
     */
    public function __construct(Connection $connection, string $statement, float $elapsedMs)
    {
        parent::__construct($connection);
        $this->statement = $statement;
        $this->elapsedMs = $elapsedMs;
    }
}
