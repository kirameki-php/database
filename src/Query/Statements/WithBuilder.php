<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Database\Expression;
use Kirameki\Database\Query\QueryHandler;
use function array_map;

class WithBuilder
{
    /**
     * @param QueryHandler $handler
     * @param list<With> $with
     */
    public function __construct(
        protected QueryHandler $handler,
        protected array $with = [],
    )
    {
    }

    /**
     * @param string $name
     * @param SelectBuilder|Closure(SelectBuilder): mixed $as
     * @return static
     */
    public function with(string $name, SelectBuilder|Closure $as): static
    {
        return $this->append($name, false, $as);
    }

    /**
     * @param string $name
     * @param SelectBuilder|Closure(SelectBuilder): mixed $as
     * @return static
     */
    public function withRecursive(string $name, SelectBuilder|Closure $as): static
    {
        return $this->append($name, true, $as);
    }

    /**
     * @param string|Expression ...$columns
     * @return SelectBuilder
     */
    public function select(string|Expression ...$columns): SelectBuilder
    {
        return $this->apply(new SelectBuilder($this->handler)->columns(...$columns));
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insertInto(string $table): InsertBuilder
    {
        return $this->apply(new InsertBuilder($this->handler, $table));
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return $this->apply(new UpdateBuilder($this->handler, $table));
    }

    /**
     * @param string $table
     * @return UpsertBuilder
     */
    public function upsertInto(string $table): UpsertBuilder
    {
        return $this->apply(new UpsertBuilder($this->handler, $table));
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function deleteFrom(string $table): DeleteBuilder
    {
        return $this->apply(new DeleteBuilder($this->handler, $table));
    }

    /**
     * @param string $name
     * @param bool $recursive
     * @param SelectBuilder|Closure(SelectBuilder): mixed $as
     * @return $this
     */
    protected function append(string $name, bool $recursive, SelectBuilder|Closure $as): static
    {
        if ($as instanceof Closure) {
            $builder = new SelectBuilder($this->handler);
            $as($builder);
            $as = $builder;
        }
        $this->with[] = new With($name, $recursive, clone $as->statement);
        return $this;
    }

    /**
     * @template TQueryBuilder as QueryBuilder
     * @param TQueryBuilder $builder
     * @return TQueryBuilder
     */
    protected function apply(QueryBuilder $builder): QueryBuilder
    {
        $builder->statement->with = (clone $this)->with;
        return $builder;
    }

    /**
     * @return void
     */
    protected function __clone(): void
    {
        $this->with = array_map(static fn(With $w) => clone $w, $this->with);
    }
}
