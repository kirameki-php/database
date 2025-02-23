<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\SortOrder;
use function array_keys;
use function array_values;
use function count;
use function dump;

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

        return new static(Direction::Next, $columns, 1);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param int $page
     * @param Direction $direction
     */
    protected function __construct(
        public readonly Direction $direction,
        public readonly array $parameters,
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
        $operator = $this->getOperator($builder->getStatement());

        $builder->where(ConditionBuilder::with($columns, $operator, $values));

        return $this;
    }

    /**
     * @param SelectStatement $statement
     * @return Operator
     */
    protected function getOperator(SelectStatement $statement): Operator
    {
        $orderBy = $statement->orderBy ?? [];

        if (count($orderBy) === 0) {
            throw new LogicException('Cannot paginate with cursor without an order by clause.', [
                'statement' => $statement,
            ]);
        }

        $order = Arr::first($orderBy)->sort;

        if ($this->direction === Direction::Previous) {
            $order = $order->reverse();

            foreach ($orderBy as $column => $ordering) {
                $statement->orderBy[$column] = new Ordering(
                    $ordering->sort->reverse(),
                    $ordering->nulls,
                );
            }
        }

        return match ($order) {
            SortOrder::Ascending => Operator::GreaterThanOrEqualTo,
            SortOrder::Descending => Operator::LessThan,
        };
    }

    /**
     * @param object $object
     * @return array<string, mixed>
     */
    protected function extractParameters(object $object): array
    {
        $parameters = [];
        foreach (array_keys($this->parameters) as $name) {
            $parameters[$name] = $object->$name;
        }
        return $parameters;
    }
}
