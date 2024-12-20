<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Statements\NullOrder;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Statements\SortOrder;
use function array_is_list;
use function array_values;

readonly class WindowBuilder
{
    /**
     * @param Aggregate $aggregate
     */
    public function __construct(
        protected Aggregate $aggregate,
    )
    {
        $aggregate->isWindowFunction = true;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function partitionBy(string ...$column): static
    {
        $this->aggregate->partitionBy = array_is_list($column) ? $column : array_values($column);
        return $this;
    }

    /**
     * @param string $column
     * @param SortOrder $sort
     * @param NullOrder|null $nulls
     * @return $this
     */
    public function orderBy(
        string $column,
        SortOrder $sort = SortOrder::Ascending,
        ?NullOrder $nulls = null,
    ): static
    {
        $this->aggregate->orderBy ??= [];
        $this->aggregate->orderBy[$column] = new Ordering($sort, $nulls);
        return $this;
    }

    /**
     * @param string $column
     * @param NullOrder|null $nulls
     * @return $this
     */
    public function orderByAsc(string $column, ?NullOrder $nulls = null): static
    {
        return $this->orderBy($column, SortOrder::Ascending, $nulls);
    }

    /**
     * @param string $column
     * @param NullOrder|null $nulls
     * @return $this
     */
    public function orderByDesc(string $column, ?NullOrder $nulls = null): static
    {
        return $this->orderBy($column, SortOrder::Descending, $nulls);
    }
}
