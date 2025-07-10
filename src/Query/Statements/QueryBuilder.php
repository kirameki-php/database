<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\QueryResult;

/**
 * @template TQueryStatement of QueryStatement = QueryStatement
 * @template TRow of object = object
 */
abstract class QueryBuilder
{
    /**
     * @param QueryHandler $handler
     * @param TQueryStatement $statement
     */
    public function __construct(
        protected readonly QueryHandler $handler,
        public protected(set) QueryStatement $statement,
    )
    {
    }

    /**
     * Do a deep clone of object types
     *
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @return static
     */
    protected function copy(): static
    {
        return clone $this;
    }

    /**
     * @param Closure(QueryResult<TQueryStatement, TRow>): mixed $callback
     * @return $this
     */
    public function afterQuery(Closure $callback): static
    {
        $this->statement->afterQuery ??= [];
        $this->statement->afterQuery[] = $callback;
        return $this;
    }

    /**
     * @return QueryResult<TQueryStatement, TRow>
     */
    public function execute(): QueryResult
    {
        return $this->handler->execute($this->statement);
    }

    /**
     * @return QueryResult<TQueryStatement, TRow>
     */
    public function cursor(): QueryResult
    {
        return $this->handler->cursor($this->statement);
    }

    /**
     * @return QueryResult<TQueryStatement, TRow>
     */
    public function explain(): QueryResult
    {
        return $this->handler->explain($this->statement);
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        return $this->handler->toSql($this->statement);
    }

    /**
     * @param string $column
     * @param string $type
     * @return $this
     */
    public function cast(string $column, string $type): static
    {
        return $this->casts([$column => $type]);
    }

    /**
     * @param iterable<string, string> $casts
     * @return $this
     */
    public function casts(iterable $casts): static
    {
        $this->statement->casts ??= [];
        foreach ($casts as $column => $type) {
            $this->statement->casts[$column] = $type;
        }
        return $this;
    }

    /**
     * @param string $key
     * @param scalar $value
     * @return $this
     */
    public function setTag(string $key, mixed $value): static
    {
        return $this->withTags([$key => $value]);
    }

    /**
     * @param iterable<string, scalar> $tags
     * @return $this
     */
    public function withTags(iterable $tags): static
    {
        $_tags = $this->statement->tags ??= new Tags();
        foreach ($tags as $key => $value) {
            $_tags->set($key, $value);
        }
        return $this;
    }
}
