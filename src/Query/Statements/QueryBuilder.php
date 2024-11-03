<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Support\Tags;

/**
 * @template TQueryStatement of QueryStatement
 */
abstract class QueryBuilder
{
    /**
     * @param QueryHandler $handler
     * @param TQueryStatement $statement
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
     * @return TQueryStatement
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
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addTag(string $key, mixed $value): static
    {
        $tags = $this->statement->tags ??= new Tags();
        $tags->add($key, $value);
        return $this;
    }

    /**
     * @return QueryResult<TQueryStatement, mixed>
     */
    public function execute(): QueryResult
    {
        return $this->handler->execute($this->statement);
    }

    /**
     * @return QueryResult<TQueryStatement, mixed>
     */
    public function explain(): QueryResult
    {
        return $this->handler->explain($this->statement);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->handler->toString($this->statement);
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
}
