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

        return new static($columns, $order, 1);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param SortOrder $order
     * @param int $page
     * @param Direction $direction
     */
    protected function __construct(
        public readonly array $parameters,
        public readonly SortOrder $order,
        public readonly int $page,
        public readonly Direction $direction = Direction::Next,
    )
    {
    }

    /**
     * @param object|null $next
     * @return static|null
     */
    public function next(?object $next): ?static
    {
        if ($next === null) {
            return null;
        }

        return new static(
            $this->extractParameters($next),
            $this->order,
            $this->page + 1,
            Direction::Next,
        );
    }

    /**
     * @param object|null $previous
     * @return static|null
     */
    public function previous(?object $previous): ?static
    {
        if ($previous === null) {
            return null;
        }

        $previousPage = $this->page - 1;

        if ($previousPage < 1) {
            return null;
        }

        return new static(
            $this->extractParameters($previous),
            $this->order,
            $previousPage,
            Direction::Previous,
        );
    }

    /**
     * @internal
     * @param SelectBuilder $builder
     * @return $this
     */
    public function applyTo(SelectBuilder $builder): static
    {
        $columns = array_keys($this->parameters);
        $values = array_values($this->parameters);

        $order = match ($this->direction) {
            Direction::Next => $this->order,
            Direction::Previous => $this->order->reverse(),
        };

        $operator = match ($order) {
            SortOrder::Ascending => Operator::GreaterThanOrEqualTo,
            SortOrder::Descending => Operator::LessThan,
        };

        $builder->where(ConditionBuilder::with($columns, $operator, $values));

        foreach ($columns as $column) {
            $builder->orderBy($column, $order);
        }

        return $this;
    }

    /**
     * @param object $object
     * @return array<string, mixed>
     */
    protected function extractParameters(object $object): array
    {
        $parameters = [];
        foreach ($this->parameters as $name => $value) {
            $parameters[$name] = $object->$name;
        }
        return $parameters;
    }
}
