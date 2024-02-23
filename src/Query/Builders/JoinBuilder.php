<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Support\JoinType;
use LogicException;
use Webmozart\Assert\Assert;

class JoinBuilder
{
    /**
     * @var JoinDefinition
     */
    protected JoinDefinition $definition;

    /**
     * @var ConditionBuilder|null
     */
    protected ?ConditionBuilder $condition = null;

    /**
     * @param JoinType $type
     * @param string $table
     */
    public function __construct(JoinType $type, string $table)
    {
        $this->definition = new JoinDefinition($type, $table);
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function on(mixed ...$args): static
    {
        return $this->applyAndCondition($this->buildOnColumnCondition(...$args));
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function where(mixed ...$args): static
    {
        return $this->applyAndCondition($this->buildCondition(...$args));
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function orOn(mixed ...$args): static
    {
        return $this->applyOrCondition($this->buildOnColumnCondition(...$args));
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function orWhere(mixed ...$args): static
    {
        return $this->applyOrCondition($this->buildCondition(...$args));
    }

    /**
     * @param ConditionBuilder $condition
     * @return $this
     */
    protected function applyAndCondition(ConditionBuilder $condition): static
    {
        if ($this->condition === null) {
            $this->condition = $condition;
            $this->definition->condition = $condition->getDefinition();
        } else {
            $this->condition->and()->apply($condition);
        }

        return $this;
    }

    /**
     * @param ConditionBuilder $condition
     * @return $this
     */
    protected function applyOrCondition(ConditionBuilder $condition): static
    {
        if ($this->condition === null) {
            throw new LogicException('on or where must be defined before applying or condition');
        }
        $this->condition->or()->apply($condition);
        return $this;
    }

    /**
     * @param mixed ...$args
     * @return ConditionBuilder
     */
    protected function buildOnColumnCondition(mixed ...$args): ConditionBuilder
    {
        Assert::countBetween($args, 2, 3);

        array_splice($args, -1, 1, [new Column(end($args))]);

        return ConditionBuilder::fromArgs(...$args);
    }

    /**
     * @param mixed ...$args
     * @return ConditionBuilder
     */
    protected function buildCondition(mixed ...$args): ConditionBuilder
    {
        Assert::countBetween($args, 1, 3);

        return ConditionBuilder::fromArgs(...$args);
    }

    /**
     * @return JoinDefinition
     */
    public function getDefinition(): JoinDefinition
    {
        return $this->definition;
    }
}
