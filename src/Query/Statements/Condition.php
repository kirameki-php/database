<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

abstract class Condition
{
    /**
     * @param mixed $value
     * @param Logic|null $logic
     * @param Condition|null $next
     */
    public function __construct(
        public mixed $value,
        public ?Logic $logic = null,
        public ?self $next = null,
    ) {
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
        }
    }
}
