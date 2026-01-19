<?php declare(strict_types=1);

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Database\Connection;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Expression;
use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\QueryBuilder;
use Kirameki\Database\Query\Statements\QueryStatement;
use Kirameki\Database\Query\Statements\RawBuilder;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Kirameki\Database\Query\Statements\UpsertBuilder;
use Kirameki\Database\Query\Statements\WithBuilder;
use Kirameki\Database\Query\Statements\WithRecursiveBuilder;
use Kirameki\Event\EventEmitter;
use function array_walk;

/**
 * @template TRow of mixed = mixed
 */
class QueryHandler
{
    /**
     * @param Connection $connection
     * @param EventEmitter|null $events
     */
    public function __construct(
        protected readonly Connection $connection,
        protected readonly ?EventEmitter $events = null,
    )
    {
    }

    /**
     * @param string|Expression ...$columns
     * @return SelectBuilder<TRow>
     */
    public function select(string|Expression ...$columns): SelectBuilder
    {
        return new SelectBuilder($this)->columns(...$columns);
    }

    /**
     * @param string $table
     * @return InsertBuilder<TRow>
     */
    public function insertInto(string $table): InsertBuilder
    {
        return new InsertBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return UpdateBuilder<TRow>
     */
    public function update(string $table): UpdateBuilder
    {
        return new UpdateBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return UpsertBuilder<TRow>
     */
    public function upsertInto(string $table): UpsertBuilder
    {
        return new UpsertBuilder($this, $table);
    }

    /**
     * @param string $table
     * @return DeleteBuilder<TRow>
     */
    public function deleteFrom(string $table): DeleteBuilder
    {
        return new DeleteBuilder($this, $table);
    }

    /**
     * @param string $name
     * @param list<string> $columns
     * @param QueryBuilder|Closure(SelectBuilder): mixed|null $as
     * @return WithBuilder
     */
    public function with(string $name, iterable $columns = [], QueryBuilder|Closure|null $as = null): WithBuilder
    {
        return new WithBuilder($this)->with($name, $columns, $as);
    }

    /**
     * @param string $name
     * @param list<string> $columns
     * @param QueryBuilder|Closure(SelectBuilder): mixed|null $as
     * @return WithRecursiveBuilder
     */
    public function withRecursive(string $name, iterable $columns = [], QueryBuilder|Closure|null $as = null): WithRecursiveBuilder
    {
        return new WithRecursiveBuilder($this)->withRecursive($name, $columns, $as);
    }

    /**
     * @param string $template
     * @param iterable<int, mixed> $parameters
     * @return RawBuilder
     */
    public function raw(string $template, iterable $parameters = []): RawBuilder
    {
        return new RawBuilder($this, $template, $parameters);
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, TRow>
     */
    public function execute(QueryStatement $statement): QueryResult
    {
        $this->preProcess($statement);
        $result = $this->connection->adapter->runQuery($statement);
        return $this->postProcess($statement, $result);
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, TRow>
     */
    public function cursor(QueryStatement $statement): QueryResult
    {
        $this->preProcess($statement);
        $result = $this->connection->adapter->runQueryWithCursor($statement);
        return $this->postProcess($statement, $result);
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @return QueryResult<TQueryStatement, TRow>
     */
    public function explain(QueryStatement $statement): QueryResult
    {
        $this->preProcess($statement);
        $result = $this->connection->adapter->explainQuery($statement);
        return $this->postProcess($statement, $result);
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
        $this->checkForDropProtection($statement);
        $this->connection->connectIfNotConnected();
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @param QueryResult<TQueryStatement, TRow> $result
     * @return QueryResult<TQueryStatement, TRow>
     */
    protected function postProcess(QueryStatement $statement, QueryResult $result): QueryResult
    {
        $this->runAfterQueryCallbacks($statement, $result);
        $this->events?->emit(new QueryExecuted($this->connection, $result));
        return $result;
    }

    /**
     * @template TQueryStatement of QueryStatement
     * @param TQueryStatement $statement
     * @param QueryResult<TQueryStatement, TRow> $result
     * @return void
     */
    protected function runAfterQueryCallbacks(QueryStatement $statement, QueryResult $result): void
    {
        if ($statement->afterQuery !== null) {
            array_walk($statement->afterQuery, $result->pipe(...));
        }
    }

    /**
     * @param QueryStatement $statement
     * @return void
     */
    protected function mergeConnectionTags(QueryStatement $statement): void
    {
        $tags = $this->connection->tags;

        if ($tags->isEmpty()) {
            return;
        }

        $statement->tags !== null
            ? $statement->tags->merge($tags)
            : $statement->tags = $tags;
    }

    protected function checkForDropProtection(QueryStatement $statement): void
    {
        if (
            $statement instanceof DeleteStatement &&
            $statement->where === null &&
            $this->connection->adapter->databaseConfig->dropProtection
        ) {
            throw new DropProtectionException('DELETE without a WHERE clause is prohibited by configuration.', [
                'statement' => $statement,
            ]);
        }
    }
}
