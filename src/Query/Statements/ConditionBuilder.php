<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Closure;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Value;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Expressions\Expression;
use Kirameki\Database\Query\Expressions\Raw;
use Kirameki\Database\Query\Support\Operator;
use Kirameki\Database\Query\Support\Range;
use Traversable;
use function array_key_exists;
use function assert;
use function count;
use function is_iterable;
use function iterator_to_array;

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
            throw new InvalidArgumentException('Expected: ' . static::class . 'Got: ' . Value::getType($args[0]) . '.', [
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
            throw new InvalidArgumentException('Missing column parameter', [
                'args' => $args,
            ]);
        }

        $self = self::for($column);
        $key = key($args);
        $value = $args[$key];

        return match ($key) {
            1, 'eq' => match (true) {
                $value instanceof Range => $self->inRange($value),
                is_iterable($value) => $self->in($value),
                default => $self->equals($value),
            },
            'not' => match (true) {
                $value instanceof Range => $self->notInRange($value),
                is_iterable($value) => $self->notIn($value),
                default => $self->equals($value),
            },
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
            default => throw new InvalidArgumentException('Unknown operator: ' . $key),
        };
    }

    /**
     * @param string|Expression $column
     * @return static
     */
    public static function for(string|Expression $column): static
    {
        return new static($column);
    }

    /**
     * @param string $raw
     * @return static
     */
    public static function raw(string $raw): static
    {
        return (new static())->expr(new Raw($raw));
    }

    /**
     * @param string|Column|null $column
     */
    protected function __construct(string|Column|null $column = null)
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
        $this->current->nextLogic = 'AND';
        $this->current->next = new ConditionDefinition($column ?? $this->current->column);
        return $this->setCurrent($this->current->next);
    }

    /**
     * @param string|null $column
     * @return static
     */
    public function or(?string $column = null): static
    {
        $this->current->nextLogic = 'OR';
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
        $value = $this->toValue($value);

        if (is_iterable($value)) {
            throw new LogicException('Iterable should use in(iterable $iterable) method instead', [
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
        return $this->define(Operator::GreaterThanOrEqualTo, $this->toValue($value));
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function greaterThan(mixed $value): static
    {
        return $this->define(Operator::GreaterThan, $this->toValue($value));
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThanOrEqualTo(mixed $value): static
    {
        return $this->define(Operator::LessThanOrEqualTo, $this->toValue($value));
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function lessThan(mixed $value): static
    {
        return $this->define(Operator::LessThan, $this->toValue($value));
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
        return $this->define(Operator::Like, $this->toValue($value));
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
     * @param iterable<mixed>|SelectBuilder $values
     * @return $this
     */
    public function in(iterable|SelectBuilder $values): static
    {
        $_values = $this->toValue($values);

        if (is_iterable($_values)) {
            $_values = ($_values instanceof Traversable) ? iterator_to_array($_values) : (array) $_values;
            $_values = array_filter($_values, static fn($s) => $s !== null);
            $_values = array_unique($_values);
        }

        return $this->define(Operator::In, $_values);
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
     * @param Range $range
     * @return $this
     */
    public function inRange(Range $range): static
    {
        return $this->define(Operator::Range, $range);
    }

    /**
     * @param Range $range
     * @return $this
     */
    public function notInRange(Range $range): static
    {
        return $this->inRange($range)->negate();
    }

    /**
     * @param Expression $expr
     * @return $this
     */
    public function expr(Expression $expr): static
    {
        return $this->define(Operator::Raw, $expr);
    }

    /**
     * @param SelectBuilder $builder
     * @return $this
     */
    public function exists(SelectBuilder $builder): static
    {
        return $this->define(Operator::Exists, $builder);
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
            $value = $value->getStatement();
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

    /**
     * @param mixed $var
     * @return mixed
     */
    protected function toValue(mixed $var): mixed
    {
        return ($var instanceof Closure) ? $var() : $var;
    }
}
