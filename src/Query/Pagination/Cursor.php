<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Query\Statements\Tuple;
use function array_keys;
use function array_values;
use function count;

class Cursor
{
    /**
     * @param SelectBuilder $builder
     * @param object|null $next
     * @return static
     */
    public static function init(
        SelectBuilder $builder,
        ?object $next,
    ): static
    {
        $orderBy = $builder->statement->orderBy ?? [];

        if (count($orderBy) === 0) {
            throw new LogicException('Cannot paginate with cursor without an order by clause.', [
                'builder' => $builder,
            ]);
        }

        $columns = array_keys($orderBy);
        $parameters = static::extractParameters($columns, $next);
        return new static($parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function __construct(
        public readonly array $parameters,
    )
    {
    }

    /**
     * @param object $next
     * @return static
     */
    public function toNext(object $next): static
    {
        $columns = array_keys($this->parameters);
        $parameters = static::extractParameters($columns, $next);
        return new static($parameters);
    }

    /**
     * @internal
     * @param SelectBuilder $builder
     * @return $this
     */
    public function applyTo(SelectBuilder $builder): static
    {
        $parameters = $this->parameters;
        $columns = new Tuple(...array_keys($parameters));
        $values = new Tuple(...array_values($parameters));
        $operator = $this->getOperator($builder->statement);

        $builder->where($columns, $operator, $values);

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

        return match ($order) {
            SortOrder::Ascending => Operator::GreaterThan,
            SortOrder::Descending => Operator::LessThan,
        };
    }

    /**
     * @param list<string> $columns
     * @param object $object
     * @return array<string, mixed>
     */
    protected static function extractParameters(array $columns, ?object $object): array
    {
        $parameters = [];
        foreach ($columns as $column) {
            $parameters[$column] = $object?->$column;
        }
        return $parameters;
    }
}
