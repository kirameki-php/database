<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Expression;
use Kirameki\Database\Query\Statements\NullOrder;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Syntax;
use Override;
use function array_is_list;
use function array_values;

/**
 * @implements Expression<QuerySyntax>
 */
class WindowBuilder implements Expression
{
    /**
     * @param QueryFunction $func
     * @param WindowDefinition $definition
     */
    public function __construct(
        public readonly QueryFunction $func,
        public readonly WindowDefinition $definition,
    )
    {
    }

    /**
     * @param string $column
     * @return $this
     */
    public function partitionBy(string ...$column): static
    {
        $this->definition->partitionBy = array_is_list($column) ? $column : array_values($column);
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
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $this->func->toValue($syntax);
    }
}
