<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Result;
use Kirameki\Database\Statements\StatementBuilder;


/**
 * @template TStatement of QueryStatement
 */
abstract class QueryBuilder implements StatementBuilder
{
    /**
     * @param Connection $connection
     * @param QueryStatement $statement
     */
    public function __construct(
        protected readonly Connection $connection,
        protected QueryStatement $statement,
    )
    {
    }

    /**
     * Do a deep clone of object types
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return QueryStatement
     */
    public function getStatement(): QueryStatement
    {
        return $this->statement;
    }

    /**
     * @return static
     */
    protected function copy(): static
    {
        return clone $this;
    }

    /**
     * @return Result
     */
    public function execute(): Result
    {
        return $this->connection->query($this->statement);
    }
}
