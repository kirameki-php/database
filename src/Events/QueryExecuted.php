<?php declare(strict_types=1);

namespace Kirameki\Database\Events;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Result;
use Kirameki\Database\Query\ResultLazy;

class QueryExecuted extends DatabaseEvent
{
    /**
     * @var Result|ResultLazy
     */
    public readonly Result|ResultLazy $result;

    /**
     * @param Connection $connection
     * @param Result|ResultLazy $result
     */
    public function __construct(Connection $connection, Result|ResultLazy $result)
    {
        parent::__construct($connection);
        $this->result = $result;
    }
}
