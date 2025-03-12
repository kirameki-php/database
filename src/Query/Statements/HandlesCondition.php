<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Value;
use Kirameki\Database\Expression;
use function array_key_exists;
use function assert;
use function count;
use function is_iterable;
use function is_string;
use function key;
use function strtoupper;

trait HandlesCondition
{
    /**
     * @param ConditionContext $context
     * @param Logic $logic
     * @param array<mixed> $args
     * @return void
     */
    protected function applyCondition(ConditionContext $context, Logic $logic, array $args): void
    {
        $condition = $this->tryCreatingCondition($args);

        if ($condition !== null) {
            $context->apply($logic, $condition);
        }
    }

    /**
     * @param array<mixed> $args
     * @return Condition|null
     */
    protected function tryCreatingCondition(array $args): ?Condition
    {
        $num = count($args);

        return match ($num) {
            1 => $this->createConditionFromOneArg($args),
            2 => $this->createConditionFromTwoArgs($args),
            3 => $this->createConditionThreeArgs(...$args),
            default => throw new LogicException("Invalid number of arguments. Expected: <= 3. Got: {$num}.", [
                'args' => $args,
            ]),
        };
    }

    /**
     * @param array<mixed> $args
     * @return Condition|null
     */
    protected function createConditionFromOneArg(array $args): ?Condition
    {
        $value = $args[0];

        return match (true) {
            $value instanceof Condition => clone $value,
            $value instanceof ConditionBuilder => $this->createConditionFromBuilder($value),
            $value instanceof Closure => $this->createConditionFromClosure($value),
            default => throw new InvalidArgumentException('Expected: Condition|ConditionBuilder|Closure. Got: ' . Value::getType($value) . '.', [
                'args' => $args,
            ]),
        };
    }

    /**
     * @param array<mixed> $args
     * @return FilteringCondition
     */
    protected function createConditionFromTwoArgs(array $args): FilteringCondition
    {
        assert(count($args) <= 2, 'Missing column parameter');

        if (array_key_exists(0, $args)) {
            $column = $args[0];
            unset($args[0]);
        } elseif (array_key_exists('column', $args)) {
            $column = $args['column'];
            unset($args['column']);
        } else {
            throw new InvalidArgumentException('Missing column parameter.', [
                'args' => $args,
            ]);
        }

        $key = key($args);
        $value = $args[$key];
        $operator = match ($key) {
            1 => match (true) {
                $value instanceof Bounds => Operator::InRange,
                is_iterable($value) => Operator::In,
                default => Operator::Equals,
            },
            'not' => match (true) {
                $value instanceof Bounds => Operator::NotInRange,
                is_iterable($value) => Operator::NotIn,
                default => Operator::NotEquals,
            },
            'eq' => Operator::Equals,
            'ne' => Operator::NotEquals,
            'gt' => Operator::GreaterThan,
            'gte' => Operator::GreaterThanOrEqualTo,
            'lt' => Operator::LessThan,
            'lte' => Operator::LessThanOrEqualTo,
            'in' => Operator::In,
            'notIn' => Operator::NotIn,
            'between' => Operator::Between,
            'notBetween' => Operator::NotBetween,
            'like' => Operator::Like,
            'notLike' => Operator::NotLike,
            default => throw new InvalidArgumentException("Unknown operator: \"{$key}\".", [
                'column' => $column,
                'key' => $key,
                'value' => $value,
            ]),
        };

        return $this->createConditionThreeArgs($column, $operator, $value);
    }

    /**
     * @param string|iterable<int, string>|Expression $column
     * @param Operator $operator
     * @param mixed $value
     * @return FilteringCondition
     */
    protected function createConditionThreeArgs(
        string|iterable|Expression $column,
        string|Operator $operator,
        mixed $value,
    ): FilteringCondition
    {
        if (is_string($operator)) {
            $operator = Operator::from(strtoupper($operator));
        }

        if ($value instanceof SelectBuilder) {
            $value = $value->statement;
        }

        return new FilteringCondition($column, $operator, $value);
    }

    /**
     * @param Closure(ConditionBuilder): mixed $callback
     * @return NestedCondition|null
     */
    protected function createConditionFromClosure(Closure $callback): ?NestedCondition
    {
        $builder = new ConditionBuilder();
        $callback($builder);
        return $this->createConditionFromBuilder($builder);
    }

    /**
     * @param ConditionBuilder $builder
     * @return NestedCondition|null
     */
    protected function createConditionFromBuilder(ConditionBuilder $builder): ?NestedCondition
    {
        $root = null;
        $current = null;
        foreach ($builder->entries as $entry) {
            $condition = $this->tryCreatingCondition($entry['args']);
            $root ??= $condition;
            if ($current !== null) {
                $current->logic = $entry['logic'];
                $current->next = $condition;
            }
            $current = $condition;
        }
        return $root !== null
            ? new NestedCondition($root)
            : null;
    }
}
