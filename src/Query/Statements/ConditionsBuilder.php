<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Expressions\Column;
use function assert;
use function count;

/**
 * @template TQueryStatement of ConditionsStatement
 * @extends QueryBuilder<TQueryStatement>
 */
abstract class ConditionsBuilder extends QueryBuilder
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
        $condition = $this->buildCondition(...$args);
        $this->appendConditionToStatement($condition);
        return $this->setLastCondition($condition);
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
        return $this->where(ConditionBuilder::raw($raw));
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

        throw new LogicException('and called without a previous condition. Define a where before declaring and', [
            'statement' => $this->statement,
        ]);
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

        throw new LogicException('or called without a previous condition. Define a where before declaring or', [
            'statement' => $this->statement,
        ]);
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
     * @param ConditionBuilder $condition
     * @return $this
     */
    protected function appendConditionToStatement(ConditionBuilder $condition): static
    {
        $this->statement->where ??= [];
        $this->statement->where[] = $condition->getDefinition();
        return $this;
    }

    /**
     * @param ConditionBuilder $condition
     * @return $this
     */
    protected function setLastCondition(ConditionBuilder $condition): static
    {
        $this->lastCondition = $condition;
        return $this;
    }
}
