<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;

/**
 * @template TRow of mixed = mixed
 * @extends QueryBuilder<SelectStatement, TRow>
 */
class CompoundBuilder extends QueryBuilder
{
    /**
     * @use ResultHelpers<TRow>
     */
    use ResultHelpers;

    /**
     * @var Compound
     */
    protected Compound $compound;

    /**
     * @param QueryHandler $handler
     * @param CompoundType $operator
     * @param SelectStatement $root
     * @param SelectStatement $query
     */
    public function __construct(
        QueryHandler $handler,
        CompoundType $operator,
        SelectStatement $root,
        SelectStatement $query,
    )
    {
        parent::__construct($handler, $root);

        $this->compound = new Compound($operator, $query);
        $root->compound = $this->compound;
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
        $this->compound->orderBy ??= [];
        $this->compound->orderBy[$column] = new Ordering($sort, $nulls);
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
        $this->compound->orderBy = null;
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
        $this->compound->limit = $count;
        return $this;
    }

    #endregion limiting ------------------------------------------------------------------------------------------------
}
