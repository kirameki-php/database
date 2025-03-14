<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Expression;
use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\Tags;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Kirameki\Database\Query\Statements\UpsertBuilder;
use Kirameki\Database\Query\Statements\WithBuilder;
use Kirameki\Event\EventEmitter;

readonly class QueryHandler
{
    /**
     * @param Connection $connection
     * @param EventEmitter|null $events
     * @param Tags|null $tags
     */
    public function __construct(
        public Connection $connection,
        protected ?EventEmitter $events = null,
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
        return new SelectBuilder($this)->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        return new InsertBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return new UpdateBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return UpsertBuilder
     */
    public function upsertInto(string $table): UpsertBuilder
    {
        return new UpsertBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function deleteFrom(string $table): DeleteBuilder
    {
        return new DeleteBuilder($this, $table);
    }

    /**
     * @param string $name
     * @param SelectBuilder|Closure(SelectBuilder): mixed $as
     * @return WithBuilder
     */
    public function with(string $name, SelectBuilder|Closure $as): WithBuilder
    {
        return new WithBuilder($this)->with($name, $as);
    }

    /**
     * @param string $name
     * @param SelectBuilder|Closure(SelectBuilder): mixed $as
     * @return WithBuilder
     */
    public function withRecursive(string $name, SelectBuilder|Closure $as): WithBuilder
    {
        return new WithBuilder($this)->withRecursive($name, $as);
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, mixed>
     */
    public function execute(QueryStatement $statement): QueryResult
    {
        $this->preProcess($statement);
        $result = $this->connection->adapter->runQuery($statement);
        return $this->postProcess($result);
    }

    /**
     * @param string $query
     * @param iterable<array-key, mixed> $parameters
     * @param array<string, string>|null $casts
     * @param Tags|null $tags
     * @return QueryResult<RawStatement, mixed>
     */
    public function executeRaw(string $query, iterable $parameters = [], ?array $casts = null, ?Tags $tags = null): QueryResult
    {
        return $this->execute(new RawStatement($query, $parameters, $casts, $tags));
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, mixed>
     */
    public function cursor(QueryStatement $statement): QueryResult
    {
        $this->preProcess($statement);
        $result = $this->connection->adapter->runQueryWithCursor($statement);
        return $this->postProcess($result);
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, mixed>
     */
    public function explain(QueryStatement $statement): QueryResult
    {
        $this->preProcess($statement);
        $result = $this->connection->adapter->explainQuery($statement);
        return $this->postProcess($result);
    }

    /**
     * @param QueryStatement $statement
     * @return string
     */
    public function toSql(QueryStatement $statement): string
    {
        return $statement->toSql($this->connection->adapter->querySyntax);
    }

    /**
     * @param QueryStatement $statement
     * @return void
     */
    protected function preProcess(QueryStatement $statement): void
    {
        $this->mergeConnectionTags($statement);
        $this->connection->connectIfNotConnected();
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @template TRow of mixed
     * @param QueryResult<TQueryStatement, TRow> $result
     * @return QueryResult<TQueryStatement, TRow>
     */
    protected function postProcess(QueryResult $result): QueryResult
    {
        $this->events?->emit(new QueryExecuted($this->connection, $result));
        return $result;
    }

    /**
     * @param QueryStatement $statement
     * @return void
     */
    protected function mergeConnectionTags(QueryStatement $statement): void
    {
        if ($this->tags !== null) {
            $statement->tags !== null
                ? $statement->tags->merge($this->tags)
                : $statement->tags = $this->tags;
        }
    }
}
