<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Closure;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Support\SortOrder;
use LogicException;
use Webmozart\Assert\Assert;

/**
 * @property ConditionsStatement $statement
 */
abstract class ConditionsBuilder extends StatementBuilder
{
    /**
     * @var ConditionBuilder|null
     */
    protected ConditionBuilder|null $lastCondition = null;

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function where(mixed ...$args): static
    {
        Assert::countBetween($args, 1, 3);
        $this->lastCondition = $this->buildCondition(...$args);
        return $this->addWhereCondition($this->lastCondition->getDefinition());
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function whereNot(mixed ...$args): static
    {
        // only 2 because operators are not supported for NOT's
        Assert::countBetween($args, 1, 2);
        $this->lastCondition = $this->buildCondition(...$args)->negate();
        return $this->addWhereCondition($this->lastCondition->getDefinition());
    }

    /**
     * @param string ...$args
     * @return $this
     */
    public function whereColumn(string ...$args): static
    {
        Assert::countBetween($args, 2, 3);

        $num = count($args);

        // the last column will be converted to Column class since there is no way to distinguish between
        // a column identifier and a string literal after this point.
        return $num === 3
            ? $this->where($args[0], $args[1], new Column($args[2]))
            : $this->where($args[0], new Column($args[1]));
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
     * @param string $column
     * @param iterable<mixed>|Closure(SelectBuilder): SelectBuilder|SelectBuilder $values
     * @return $this
     */
    public function whereIn(string $column, iterable|Closure|SelectBuilder $values): static
    {
        $this->lastCondition = $this->buildCondition($column, 'IN' , $values);
        return $this->addWhereCondition($this->lastCondition->getDefinition());
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function and(mixed ...$args): static
    {
        Assert::countBetween($args, 1, 3);
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
        Assert::countBetween($args, 1, 3);
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
