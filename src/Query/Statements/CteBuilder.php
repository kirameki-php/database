<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Database\Expression;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\Statements\QueryBuilder as TQueryBuilder;
use function iterator_to_array;

abstract class CteBuilder
{
    /**
     * @var CteAggregate
     */
    public protected(set) CteAggregate $cteAggregate;

    /**
     * @param QueryHandler $handler
     * @param bool $recursive
     */
    public function __construct(
        protected QueryHandler $handler,
        bool $recursive,
    )
    {
        $this->cteAggregate = new CteAggregate($recursive);
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
     * @return UpdateBuilder
     */
    public function update(string $table): UpdateBuilder
    {
        return $this->apply(new UpdateBuilder($this->handler, $table));
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
     * @param iterable<int, string> $columns
     * @param QueryBuilder|Closure(SelectBuilder): mixed|null $as
     * This is nullable so that named arguments can be used to skip the columns.
     * @return $this
     */
    protected function addCte(
        string $name,
        iterable $columns,
        QueryBuilder|Closure|null $as = null,
    ): static
    {
        if ($as === null) {
            throw new InvalidArgumentException('The "as" argument must be provided.', [
                'name' => $name,
                'columns' => iterator_to_array($columns),
            ]);
        }

        if ($as instanceof Closure) {
            $builder = new SelectBuilder($this->handler);
            $as($builder);
            $as = $builder;
        }

        $this->cteAggregate->add(new Cte(
            $name,
            Arr::values($columns),
            clone $as->statement,
        ));

        return $this;
    }

    /**
     * @template TQueryBuilder as WhereBuilder
     * @param TQueryBuilder $builder
     * @return TQueryBuilder
     */
    protected function apply(WhereBuilder $builder): WhereBuilder
    {
        $builder->statement->with = $this->cteAggregate;
        return $builder;
    }
}
