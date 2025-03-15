<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Database\Expression;
use Kirameki\Database\Query\QueryHandler;
use function array_map;
use function iterator_to_array;

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
     * @param list<string> $columns
     * @param SelectBuilder|Closure(SelectBuilder): mixed|null $as
     * @return static
     */
    public function with(string $name, iterable $columns = [], SelectBuilder|Closure|null $as = null): static
    {
        return $this->append($name, false, $columns, $as);
    }

    /**
     * @param string $name
     * @param list<string> $columns
     * @param SelectBuilder|Closure(SelectBuilder): mixed|null $as
     * @return static
     */
    public function withRecursive(string $name, iterable $columns = [], SelectBuilder|Closure|null $as = null): static
    {
        return $this->append($name, true, $columns, $as);
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
     * @param iterable<int, string> $columns
     * @param SelectBuilder|Closure(SelectBuilder): mixed|null $as
     * This is nullable so that named arguments can be used to skip the columns.
     * @return $this
     */
    protected function append(string $name, bool $recursive, iterable $columns, SelectBuilder|Closure|null $as = null): static
    {
        if ($as === null) {
            throw new InvalidArgumentException('The "as" argument must be provided.', [
                'name' => $name,
                'recursive' => $recursive,
                'columns' => iterator_to_array($columns),
            ]);
        }

        if ($as instanceof Closure) {
            $builder = new SelectBuilder($this->handler);
            $as($builder);
            $as = $builder;
        }

        $this->with[] = new With(
            $name,
            $recursive,
            Arr::values($columns),
            clone $as->statement,
        );

        return $this;
    }

    /**
     * @template TQueryBuilder as QueryBuilder
     * @param TQueryBuilder $builder
     * @return TQueryBuilder
     */
    protected function apply(QueryBuilder $builder): QueryBuilder
    {
        $builder->statement->with = $this->with;
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
