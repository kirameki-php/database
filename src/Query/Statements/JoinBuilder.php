<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Support\JoinType;
use function array_is_list;
use function array_values;
use function assert;
use function func_num_args;

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
    public function __construct(
        JoinType $type,
        string $table,
    )
    {
        $this->definition = new JoinDefinition($type, $table);
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function using(string ...$columns): static
    {
        $this->definition->using = array_is_list($columns) ? $columns : array_values($columns);
        return $this;
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
    public function orOn(mixed ...$args): static
    {
        return $this->applyOrCondition($this->buildOnColumnCondition(...$args));
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
            throw new LogicException('on or where must be defined before applying or condition', [
                'definition' => $this->definition,
            ]);
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
        assert(func_num_args() >= 2 && func_num_args() <= 3);

        array_splice($args, -1, 1, [new Column(end($args))]);

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
