<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Statements\Execution;
use Kirameki\Database\Statements\Query\Expressions\Expression;
use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Event\EventManager;

readonly class QueryHandler
{
    /**
     * @param Connection $connection
     * @param EventManager $events
     * @param QuerySyntax $syntax
     */
    public function __construct(
        public Connection $connection,
        protected EventManager $events,
        protected QuerySyntax $syntax,
    )
    {
    }

    /**
     * @param string|Expression ...$columns
     * @return SelectBuilder
     */
    public function select(string|Expression ...$columns): SelectBuilder
    {
        $builder = new SelectBuilder($this, $this->syntax);
        return $builder->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        return new InsertBuilder($this, $this->syntax, $table);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return new UpdateBuilder($this, $this->syntax, $table);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table): DeleteBuilder
    {
        return new DeleteBuilder($this, $this->syntax, $table);
    }

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function execute(QueryStatement $statement): QueryResult
    {
        return $this->handleExecution($this->connection->adapter->query($statement));
    }

    /**
     * @template TStatement of QueryStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function cursor(QueryStatement $statement): QueryResult
    {
        return $this->handleExecution($this->connection->adapter->cursor($statement));
    }

    /**
     * @template TStatement of QueryStatement
     * @param QueryExecution<TStatement> $execution
     * @return QueryResult<TStatement>
     */
    protected function handleExecution(Execution $execution): QueryResult
    {
        $result = new QueryResult($this->connection, $execution);
        $this->events->emit(new QueryExecuted($this->connection, $result));
        return $result;
    }
}
