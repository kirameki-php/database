<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\QueryHandler;
use Kirameki\Database\Statements\Result;
use Kirameki\Database\Statements\StatementBuilder;

/**
 * @template TStatement of QueryStatement
 */
abstract class QueryBuilder implements StatementBuilder
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
     * @return Result
     */
    public function execute(): Result
    {
        return $this->handler->execute($this->statement);
    }

    /**
     * @return Result
     */
    public function cursor(): Result
    {
        return $this->handler->cursor($this->statement);
    }
}
