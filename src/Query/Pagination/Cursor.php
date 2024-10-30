<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\SortOrder;
use function array_keys;

/**
 * @consistent-constructor
 */
class Cursor
{
    /**
     * @param SelectBuilder $builder
     * @param int $size
     * @return static
     */
    public static function init(SelectBuilder $builder, int $size): static
    {
        $orderBy = $builder->getStatement()->orderBy ?? [];
        if ($orderBy === []) {
            throw new LogicException('Cannot paginate with cursor without an order by clause.', [
                'builder' => $builder,
            ]);
        }

        $columns = [];
        foreach ($orderBy as $column => $order) {
            $columns[$column] = null;
        }
        $sortOrder = Arr::first($orderBy)->sort;
        return new static($columns, $sortOrder, $size);
    }

    /**
     * @param CursorPaginator<mixed> $paginator
     * @param self $current
     * @return static
     */
    public static function next(CursorPaginator $paginator, self $current): static
    {
        $ref = $paginator->last();

        $columns = [];
        foreach ($current->columns as $name => $value) {
            $columns[$name] = $ref[$name];
        }

        return new static($columns, $current->order, $current->size, $current->page + 1);
    }

    /**
     * @param array<string, mixed> $columns
     * @param SortOrder $order
     * @param int $size
     * @param int $page
     */
    public function __construct(
        public readonly array $columns,
        public readonly SortOrder $order,
        public readonly int $size,
        public readonly int $page = 1,
    )
    {
    }

    /**
     * @param SelectBuilder $builder
     * @return void
     */
    public function apply(SelectBuilder $builder): void
    {
        $columns = array_keys($this->columns);
        $values = array_values($this->columns);
        $operator = match ($this->order) {
            SortOrder::Ascending => Operator::GreaterThan,
            SortOrder::Descending => Operator::LessThan,
        };

        $builder->where(ConditionBuilder::with($columns, $operator, $values));

        foreach ($columns as $column) {
            $builder->orderBy($column, $this->order);
        }
    }
}
