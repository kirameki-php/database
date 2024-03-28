<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Query\Expressions\Expression;
use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement as TStatement;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Kirameki\Database\Query\Syntax\QuerySyntax;
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
     * @template TStatement of TStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function execute(TStatement $statement): QueryResult
    {
        return $this->processResult($this->connection->adapter->query($statement));
    }

    /**
     * @template TStatement of TStatement
     * @param TStatement $statement
     * @return QueryResult<TStatement>
     */
    public function cursor(TStatement $statement): QueryResult
    {
        return $this->processResult($this->connection->adapter->cursor($statement));
    }

    /**
     * @template TStatement of TStatement
     * @param QueryResult<TStatement> $result
     * @return QueryResult<TStatement>
     */
    protected function processResult(QueryResult $result): QueryResult
    {
        $this->events->emit(new QueryExecuted($this->connection, $result));
        return $result;
    }
}
