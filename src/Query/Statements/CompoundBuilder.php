<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class CompoundBuilder
{
    protected CompoundDefinition $definition;

    /**
     * @param CompoundOperator $operator
     * @param SelectStatement $query
     */
    public function __construct(
        CompoundOperator $operator,
        SelectStatement $query,
    )
    {
        $this->definition = new CompoundDefinition($operator, $query);
    }

    /**
     * @return CompoundDefinition
     */
    public function getDefinition(): CompoundDefinition
    {
        return $this->definition;
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
        $this->definition->orderBy ??= [];
        $this->definition->orderBy[$column] = new Ordering($sort, $nulls);
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
        $this->definition->orderBy = null;
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
        $this->definition->limit = $count;
        return $this;
    }

    #endregion limiting ------------------------------------------------------------------------------------------------
}
