<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Value;
use Kirameki\Database\Expression;
use Kirameki\Database\Raw;
use function array_key_exists;
use function assert;
use function count;
use function is_iterable;
use function is_string;

/**
 * @consistent-constructor
 */
class ConditionBuilder
{
    /**
     * @var ConditionDefinition
     */
    protected ConditionDefinition $root;

    /**
     * @var ConditionDefinition
     */
    protected ConditionDefinition $current;

    /**
     * @var bool
     */
    protected bool $defined;

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function fromArgs(mixed ...$args): static
    {
        $num = count($args);

        if ($num === 1) {
            if ($args[0] instanceof static) {
                return $args[0];
            }
            throw new InvalidArgumentException('Expected: ' . static::class . '. Got: ' . Value::getType($args[0]) . '.', [
                'args' => $args,
            ]);
        }

        if ($num === 2) {
            return self::fromNamedArgs($args);
        }

        throw new LogicException("Invalid number of arguments. Expected: <= 2. Got: {$num}.", [
            'args' => $args,
        ]);
    }

    /**
     * @param array<mixed> $args
     * @return static
     */
    protected static function fromNamedArgs(array $args): static
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

        $self = new static($column);
        $key = key($args);
        $value = $args[$key];

        return match ($key) {
            1 => match (true) {
                $value instanceof Bounds => $self->inRange($value),
                is_iterable($value) => $self->in($value),
                default => $self->equals($value),
            },
            'not' => match (true) {
                $value instanceof Bounds => $self->notInRange($value),
                is_iterable($value) => $self->notIn($value),
                default => $self->notEquals($value),
            },
            'eq' => $self->equals($value),
            'ne' => $self->notEquals($value),
            'gt' => $self->greaterThan($value),
            'gte' => $self->greaterThanOrEqualTo($value),
            'lt' => $self->lessThan($value),
            'lte' => $self->lessThanOrEqualTo($value),
            'in' => $self->in($value),
            'notIn' => $self->notIn($value),
            'between' => $self->between($value[0], $value[1]),
            'notBetween' => $self->notBetween($value[0], $value[1]),
            'like' => $self->like($value),
            'notLike' => $self->notLike($value),
            default => throw new InvalidArgumentException("Unknown operator: \"{$key}\".", [
                'column' => $column,
                'key' => $key,
                'value' => $value,
            ]),
        };
    }

    /**
     * @param string|iterable<int, string>|Expression $column
     * @return static
     */
    public static function for(string|iterable|Expression $column): static
    {
        return new static($column);
    }

    /**
     * @param string|iterable<int, string>|Expression $column
     * @param Operator $operator
     * @param mixed $value
     * @return static
     */
    public static function with(string|iterable|Expression $column, Operator $operator, mixed $value): static
    {
        return new static($column)->define($operator, $value);
    }

    /**
     * @param string|Expression $raw
     * @return static
     */
    public static function raw(string|Expression $raw): static
    {
        $expr = is_string($raw)
            ? new Raw($raw)
            : $raw;

        return new static('_UNUSED_')->define(Operator::Raw, $expr);
    }

    /**
     * @param SelectBuilder $builder
     * @return $this
     */
    public static function exists(SelectBuilder $builder): static
    {
        return new static('_UNUSED_')->define(Operator::Exists, $builder);
    }

    /**
     * @param SelectBuilder $builder
     * @return $this
     */
    public static function notExists(SelectBuilder $builder): static
    {
        return static::exists($builder)->negate();
    }

    /**
     * @param string|iterable<int, string>|Expression $column
     */
    protected function __construct(string|iterable|Expression $column)
    {
        $this->root = $this->current = new ConditionDefinition($column);
        $this->defined = false;
    }

    /**
     * Do a deep clone of object types
     */
    public function __clone()
    {
        $this->root = clone $this->root;

        // $this->current should always point to the last condition
        $this->current = $this->root;
        while ($this->current->next !== null) {
            $this->current = $this->current->next;
        }
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function and(?string $column = null): static
    {
        $this->current->nextLogic = Logic::And;
        $this->current->next = new ConditionDefinition($column ?? $this->current->column);
        return $this->setCurrent($this->current->next);
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function or(?string $column = null): static
    {
        $this->current->nextLogic = Logic::Or;
        $this->current->next = new ConditionDefinition($column ?? $this->current->column);
        return $this->setCurrent($this->current->next);
    }

    /**
     * @param ConditionDefinition $next
     * @return $this
     */
    protected function setCurrent(ConditionDefinition $next): static
    {
        $this->current = $next;
        $this->defined = false;
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function equals(mixed $value): static
    {
        if (is_iterable($value)) {
            throw new InvalidArgumentException('Iterable should use in(iterable $iterable) method.', [
                'root' => $this->root,
                'current' => $this->current,
                'value' => $value,
            ]);
        }

        return $this->define(Operator::Equals, $value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function notEquals(mixed $value): static
    {
        return $this->equals($value)->negate();
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function greaterThanOrEqualTo(mixed $value): static
    {
        return $this->define(Operator::GreaterThanOrEqualTo, $value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function greaterThan(mixed $value): static
    {
        return $this->define(Operator::GreaterThan, $value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThanOrEqualTo(mixed $value): static
    {
        return $this->define(Operator::LessThanOrEqualTo, $value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThan(mixed $value): static
    {
        return $this->define(Operator::LessThan, $value);
    }

    /**
     * @return $this
     */
    public function isNull(): static
    {
        return $this->equals(null);
    }

    /**
     * @return $this
     */
    public function isNotNull(): static
    {
        return $this->isNull()->negate();
    }

    /**
     * @param string $value
     * @return $this
     */
    public function like(string $value): static
    {
        return $this->define(Operator::Like, $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function notLike(string $value): static
    {
        return $this->like($value)->negate();
    }

    /**
     * @param iterable<array-key, mixed>|SelectBuilder $values
     * @return $this
     */
    public function in(iterable|SelectBuilder $values): static
    {
        if (is_iterable($values)) {
            $values = Arr::without($values, null);
            $values = Arr::unique($values);
        }

        return $this->define(Operator::In, $values);
    }

    /**
     * @param iterable<int, mixed>|SelectBuilder $values
     * @return $this
     */
    public function notIn(iterable|SelectBuilder $values): static
    {
        return $this->in($values)->negate();
    }

    /**
     * @param mixed $min
     * @param mixed $max
     * @return $this
     */
    public function between(mixed $min, mixed $max): static
    {
        return $this->define(Operator::Between, [$min, $max]);
    }

    /**
     * @param mixed $min
     * @param mixed $max
     * @return $this
     */
    public function notBetween(mixed $min, mixed $max): static
    {
        return $this->between($min, $max)->negate();
    }

    /**
     * @param Bounds $bounds
     * @return $this
     */
    public function inRange(Bounds $bounds): static
    {
        return $this->define(Operator::Range, $bounds);
    }

    /**
     * @param Bounds $bounds
     * @return $this
     */
    public function notInRange(Bounds $bounds): static
    {
        return $this->inRange($bounds)->negate();
    }

    /**
     * @param static $builder
     * @return $this
     */
    public function apply(self $builder): static
    {
        $this->current->column = $builder->current->column;
        $this->current->operator = $builder->current->operator;
        $this->current->value = $builder->current->value;
        $this->current->negated = $builder->current->negated;
        $this->defined = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function negate(): static
    {
        $this->current->negated = !$this->current->negated;
        return $this;
    }

    /**
     * @return ConditionDefinition
     */
    public function getDefinition(): ConditionDefinition
    {
        return $this->root;
    }

    /**
     * @param Operator $operator
     * @param mixed $value
     * @return $this
     */
    protected function define(Operator $operator, mixed $value): static
    {
        $this->current->operator = $operator;

        if ($value instanceof SelectBuilder) {
            $value = $value->statement;
        }
        $this->current->value = $value;

        if ($this->defined) {
            throw new LogicException('Tried to set condition when it was already set!', [
                'current' => $this->current,
                'operator' => $operator,
                'value' => $value,
            ]);
        }
        $this->defined = true;

        return $this;
    }
}
