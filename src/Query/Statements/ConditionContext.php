<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

class ConditionContext
{
    /**
     * @param Condition|null $root
     * @param Condition|null $latest
     */
    public function __construct(
        public ?Condition $root = null,
        public ?Condition $latest = null,
    )
    {
    }

    /**
     * Do a deep clone of object types
     */
    public function __clone()
    {
        if ($this->root !== null) {
            $this->root = clone $this->root;
        }

        if ($this->latest !== null) {
            $this->latest = clone $this->latest;
        }
    }

    /**
     * @param Logic $Logic
     * @param Condition $condition
     * @return void
     */
    public function apply(Logic $Logic, Condition $condition): void
    {
        $this->setRootOnce($condition);
        $this->updateLatest($Logic, $condition);
    }

    /**
     * @param Condition $condition
     * @return void
     */
    protected function setRootOnce(Condition $condition): void
    {
        if ($this->root === null) {
            $this->root = $condition;
        }
    }

    /**
     * @param Logic $logic
     * @param Condition $condition
     * @return void
     */
    protected function updateLatest(Logic $logic, Condition $condition): void
    {
        $latest = $this->latest;

        if ($latest === null) {
            $latest = $condition;
        } else {
            $latest->logic = $logic;
            $latest->next = $condition;
        }

        while ($latest->next !== null) {
            $latest = $latest->next;
        }
        $this->latest = $latest;
    }
}
