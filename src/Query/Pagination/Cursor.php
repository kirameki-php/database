<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\SortOrder;
use function array_keys;
use function array_values;
use function count;

class Cursor
{
    /**
     * @param SelectBuilder $builder
     * @param object|null $next
     * @return static|null
     */
    public static function init(
        SelectBuilder $builder,
        ?object $next,
    ): ?static
    {
        if ($next === null) {
            return null;
        }

        $orderBy = $builder->getStatement()->orderBy ?? [];

        if (count($orderBy) === 0) {
            throw new LogicException('Cannot paginate with cursor without an order by clause.', [
                'builder' => $builder,
            ]);
        }

        $columns = [];
        foreach (array_keys($orderBy) as $column) {
            $columns[$column] = $next->$column;
        }
        $order = Arr::first($orderBy)->sort;

        return new static($columns, $order);
    }

    /**
     * @param array<string, mixed> $columns
     * @param SortOrder $order
     * @param int $page
     */
    protected function __construct(
        public readonly array $columns,
        public readonly SortOrder $order,
        public readonly int $page = 1,
    )
    {
    }

    public function next(?object $next): ?static
    {
        if ($next === null) {
            return null;
        }

        $nextColumns = [];
        foreach (array_keys($this->columns) as $name) {
            $nextColumns[$name] = $next->$name;
        }

        return new static(
            $nextColumns,
            $this->order,
            $this->page + 1,
        );
    }

    /**
     * @internal
     * @param SelectBuilder $builder
     * @return $this
     */
    public function applyTo(SelectBuilder $builder): static
    {
        $columns = array_keys($this->columns);
        $values = array_values($this->columns);
        $operator = match ($this->order) {
            SortOrder::Ascending => Operator::GreaterThanOrEqualTo,
            SortOrder::Descending => Operator::LessThan,
        };

        $builder->where(ConditionBuilder::with($columns, $operator, $values));

        foreach ($columns as $column) {
            $builder->orderBy($column, $this->order);
        }

        return $this;
    }
}
