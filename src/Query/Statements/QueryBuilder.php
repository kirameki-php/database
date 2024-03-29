<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;

/**
 * @template TStatement of QueryStatement
 */
abstract class QueryBuilder
{
    /**
     * @param QueryHandler $handler
     * @param TStatement $statement
     */
    public function __construct(
        protected readonly QueryHandler $handler,
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
     * @return TStatement
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
     * @return QueryResult<TStatement>
     */
    public function execute(): QueryResult
    {
        return $this->handler->execute($this->statement);
    }

    /**
     * @return QueryResult<TStatement>
     */
    public function cursor(): QueryResult
    {
        return $this->handler->cursor($this->statement);
    }
}
