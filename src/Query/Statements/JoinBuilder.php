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
     * @var ConditionContext
     */
    protected ConditionContext $joinContext {
        get => $this->joinContext ??= new ConditionContext();
    }

    /**
     * @var FilteringCondition|null
     */
    protected ?FilteringCondition $condition = null;

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
        $this->applyCondition($this->joinContext, Logic::And, $args);
        $this->join->condition = $this->joinContext->root;
        return $this;
    }
}
