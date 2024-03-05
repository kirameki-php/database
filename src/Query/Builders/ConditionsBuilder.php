<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Closure;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Support\SortOrder;
use LogicException;
use function assert;
use function count;

/**
 * @template TStatement of ConditionsStatement
 * @extends StatementBuilder<TStatement>
 */
abstract class ConditionsBuilder extends StatementBuilder
{
    /**
     * @var ConditionBuilder|null
     */
    protected ConditionBuilder|null $lastCondition = null;

    /**
     * @param mixed ...$args
     * Can have two different signatures:
     * 1. Has one argument. First argument is a ConditionBuilder instance.
     * 2. First argument is the column name, and the second argument is the value.
     * The second argument can also be a named parameter. The following are valid:
     * - where('column', eq: $value)
     * - where('column', not: $value)
     * - where('column', gt: $value)
     * - where('column', gte: $value)
     * - where('column', lt: $value)
     * - where('column', lte: $value)
     * - where('column', in: [$value, ...])
     * - where('column', notIn: [$value, ...])
     * - where('column', between: [$value1, $value2])
     * - where('column', notBetween: [$value1, $value2])
     * - where('column', like: "___")
     * - where('column', notLike: "%hi%")
     * @return $this
     */
    public function where(mixed ...$args): static
    {
        $this->lastCondition = $this->buildCondition(...$args);
        return $this->addWhereCondition($this->lastCondition->getDefinition());
    }

    /**
     * @param string $column
     * The column name to be used in the condition
     * @param string ...$args
     * This is defined as a variadic function so that the second argument can define
     * a named parameter, which will be passed down to where(...) method.
     * @return $this
     */
    public function whereColumn(string $column, string ...$args): static
    {
        assert(count($args) === 1);
        $key = key($args);
        $args[$key] = new Column($args[$key]);
        return $this->where($column, ...$args);
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function whereRaw(string $raw): static
    {
        return $this->addWhereCondition(ConditionBuilder::raw($raw)->getDefinition());
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function and(mixed ...$args): static
    {
        if ($this->lastCondition?->and()->apply($this->buildCondition(...$args)) !== null) {
            return $this;
        }

        throw new LogicException('and called without a previous condition. Define a where before declaring and');
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function or(mixed ...$args): static
    {
        if ($this->lastCondition?->or()->apply($this->buildCondition(...$args)) !== null) {
            return $this;
        }

        throw new LogicException('or called without a previous condition. Define a where before declaring or');
    }

    /**
     * @param string $column
     * @param SortOrder $sort
     * @return $this
     */
    public function orderBy(string $column, SortOrder $sort = SortOrder::Ascending): static
    {
        $this->statement->orderBy ??= [];
        $this->statement->orderBy[$column] = $sort;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByAsc(string $column): static
    {
        return $this->orderBy($column);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, SortOrder::Descending);
    }

    /**
     * @return $this
     */
    public function reorder(): static
    {
        $this->statement->orderBy = null;
        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count): static
    {
        $this->statement->limit = $count;
        return $this;
    }

    /**
     * @param mixed ...$args
     * @return ConditionBuilder
     */
    protected function buildCondition(mixed ...$args): ConditionBuilder
    {
        return ConditionBuilder::fromArgs(...$args);
    }

    /**
     * @param ConditionDefinition $definition
     * @return $this
     */
    protected function addWhereCondition(ConditionDefinition $definition): static
    {
        $this->statement->where ??= [];
        $this->statement->where[] = $definition;
        return $this;
    }
}
