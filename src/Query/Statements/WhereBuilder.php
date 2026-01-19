<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Expressions\Column;
use function array_key_last;
use function assert;

/**
 * @template TConditionStatement of ConditionStatement
 * @template TRow of mixed = mixed
 * @extends QueryBuilder<TConditionStatement, TRow>
 */
abstract class WhereBuilder extends QueryBuilder
{
    use HandlesCondition;

    /**
     * @var ConditionContext|null
     */
    protected ?ConditionContext $whereContext = null;

    /**
     * Do a deep clone of object types
     */
    public function __clone()
    {
        parent::__clone();

        if ($this->whereContext !== null) {
            $this->whereContext = clone $this->whereContext;
        }
    }

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
        $context = $this->getWhereContext();
        $this->applyCondition($context, Logic::And, $args);
        $this->statement->where ??= $context->root;
        return $this;
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
        $key = array_key_last($args);
        assert($key !== null);
        $args[$key] = new Column($args[$key]);
        return $this->where($column, ...$args);
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function whereRaw(string $raw): static
    {
        return $this->where(new RawCondition($raw));
    }

    /**
     * @param SelectBuilder $query
     * @return $this
     */
    public function whereExists(SelectBuilder $query): static
    {
        return $this->where(new CheckingCondition(clone $query->statement));
    }

    /**
     * @param SelectBuilder $query
     * @return $this
     */
    public function whereNotExists(SelectBuilder $query): static
    {
        return $this->where(new CheckingCondition(clone $query->statement, true));
    }

    /**
     * @return ConditionContext
     */
    protected function getWhereContext(): ConditionContext
    {
        return $this->whereContext ??= new ConditionContext();
    }
}
