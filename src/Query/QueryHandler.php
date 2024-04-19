<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Query\Expressions\Expression;
use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\QueryResult;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Database\Query\Support\Tags;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Event\EventManager;

readonly class QueryHandler
{
    /**
     * @param Connection $connection
     * @param EventManager $events
     * @param Tags|null $tags
     */
    public function __construct(
        public Connection $connection,
        protected EventManager $events,
        protected ?Tags $tags = null,
    )
    {
    }

    /**
     * @param string|Expression ...$columns
     * @return SelectBuilder
     */
    public function select(string|Expression ...$columns): SelectBuilder
    {
        return (new SelectBuilder($this, new SelectStatement()))->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        return new InsertBuilder($this, new InsertStatement($table));
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return new UpdateBuilder($this, new UpdateStatement($table));
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete(string $table): DeleteBuilder
    {
        return new DeleteBuilder($this, new DeleteStatement($table));
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement>
     */
    public function execute(QueryStatement $statement): QueryResult
    {
        $this->preprocessStatement($statement);
        return $this->processResult($this->connection->adapter->runQuery($statement));
    }

    /**
     * @param string $query
     * @param iterable<array-key, mixed> $parameters
     * @param Tags|null $tags
     * @return QueryResult<RawStatement>
     */
    public function executeRaw(string $query, iterable $parameters = [], ?Tags $tags = null): QueryResult
    {
        return $this->execute(new RawStatement($tags, $query, $parameters));
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement>
     */
    public function cursor(QueryStatement $statement): QueryResult
    {
        $this->preprocessStatement($statement);
        return $this->processResult($this->connection->adapter->runQueryWithCursor($statement));
    }

    public function toString(QueryStatement $statement): string
    {
        return $statement->toString($this->connection->adapter->getQuerySyntax());
    }

    /**
     * @param QueryStatement $statement
     * @return void
     */
    protected function preprocessStatement(QueryStatement $statement): void
    {
        if ($this->tags !== null) {
            $statement->tags !== null
                ? $statement->tags->merge($this->tags)
                : $statement->tags = $this->tags;
        }
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param QueryResult<TQueryStatement> $result
     * @return QueryResult<TQueryStatement>
     */
    protected function processResult(QueryResult $result): QueryResult
    {
        $this->events->emit(new QueryExecuted($this->connection, $result));
        return $result;
    }
}
