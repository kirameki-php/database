<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Core\Json;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\QueryBuilder;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Ordering;
use Kirameki\Database\Query\Support\SortOrder;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use function array_keys;
use function compact;
use function str_replace;

class CursorConditions
{
    /**
     * @param array<string, mixed> $columns
     * @param SortOrder $orderBy
     * @param int $size
     * @param int $page
     */
    public function __construct(
        public readonly array $columns,
        public readonly SortOrder $orderBy,
        public readonly int $size,
        public readonly int $page = 1,
    )
    {
    }

    public function apply(SelectBuilder $builder): void
    {
        $columns = array_keys($this->columns);
        $values = array_values($this->columns);
        $operator = match ($this->orderBy) {
            SortOrder::Ascending => Operator::GreaterThan,
            SortOrder::Descending => Operator::LessThan,
        };
        $condition = ConditionBuilder::for($columns)->define($operator, $values);

        $builder->where($condition);
        foreach ($columns as $column) {
            $builder->orderBy($column, $this->orderBy);
        }
    }
}
