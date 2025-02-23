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
     * @param object|null $nextRow
     * @return static|null
     */
    public static function initOrNull(
        SelectBuilder $builder,
        ?object $nextRow,
    ): ?static
    {
        if ($nextRow === null) {
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
            $columns[$column] = $nextRow->$column;
        }
        $order = Arr::first($orderBy)->sort;

        return new static(Direction::Next, $columns, $order, 1);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param SortOrder $order
     * @param int $page
     * @param Direction $direction
     */
    protected function __construct(
        public readonly Direction $direction,
        public readonly array $parameters,
        public readonly SortOrder $order,
        public readonly int $page,
    )
    {
    }

    /**
     * @param object $next
     * @return static|null
     */
    public function toNext(object $next): ?static
    {
        return new static(
            Direction::Next,
            $this->extractParameters($next),
            $this->order,
            $this->page + 1,
        );
    }

    /**
     * @param object $previous
     * @return static|null
     */
    public function toPrevious(object $previous): ?static
    {
        return new static(
            Direction::Previous,
            $this->extractParameters($previous),
            $this->order,
            $this->page - 1,
        );
    }

    /**
     * @internal
     * @param SelectBuilder $builder
     * @return $this
     */
    public function applyTo(SelectBuilder $builder): static
    {
        $parameters = $this->parameters;
        $columns = array_keys($parameters);
        $values = array_values($parameters);

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
