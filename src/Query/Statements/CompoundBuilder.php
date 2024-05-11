<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\Support\CompoundOperator;
use Kirameki\Database\Query\Support\NullOrder;
use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Support\SortOrder;

/**
 * @extends QueryBuilder<CompoundStatement>
 */
class CompoundBuilder extends QueryBuilder
{
    /**
     * @param QueryHandler $handler
     * @param CompoundOperator $operator
     * @param SelectStatement $query
     */
    public function __construct(
        QueryHandler $handler,
        CompoundOperator $operator,
        SelectStatement $query,
    )
    {
        parent::__construct($handler, new CompoundStatement($operator, $query));
    }

    #region sorting ----------------------------------------------------------------------------------------------------

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
        $this->statement->orderBy ??= [];
        $this->statement->orderBy[$column] = new Ordering($sort, $nulls);
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

    /**
     * @return $this
     */
    public function reorder(): static
    {
        $this->statement->orderBy = null;
        return $this;
    }

    #endregion sorting -------------------------------------------------------------------------------------------------

    #region limiting ---------------------------------------------------------------------------------------------------

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count): static
    {
        $this->statement->limit = $count;
        return $this;
    }

    #endregion limiting ------------------------------------------------------------------------------------------------
}
