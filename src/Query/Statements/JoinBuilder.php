<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Expressions\Column;
use function array_is_list;
use function array_splice;
use function array_values;
use function assert;
use function end;
use function func_num_args;

class JoinBuilder
{
    use HandlesCondition;

    /**
     * @var JoinDefinition
     */
    public protected(set) JoinDefinition $join;

    /**
     * @var ConditionContext|null
     */
    protected ?ConditionContext $joinContext = null;

    /**
     * @var ComparingCondition|null
     */
    protected ?ComparingCondition $condition = null;

    /**
     * @param JoinType $type
     * @param string $table
     */
    public function __construct(
        JoinType $type,
        string $table,
    )
    {
        $this->join = new JoinDefinition($type, $table);
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function using(string ...$columns): static
    {
        $this->join->using = array_is_list($columns) ? $columns : array_values($columns);
        return $this;
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function on(mixed ...$args): static
    {
        assert(func_num_args() >= 2 && func_num_args() <= 3);
        array_splice($args, -1, 1, [new Column(end($args))]);
        $context = $this->getJoinContext();
        $this->applyCondition($context, Logic::And, $args);
        $this->join->condition = $context->root;
        return $this;
    }

    /**
     * @return ConditionContext
     */
    protected function getJoinContext(): ConditionContext
    {
        return $this->joinContext ??= new ConditionContext();
    }
}
