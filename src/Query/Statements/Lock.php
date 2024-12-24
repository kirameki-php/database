<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

readonly class Lock
{
    /**
     * @param LockType $type
     * @param LockOption|null $option
     */
    public function __construct(
        public LockType $type,
        public ?LockOption $option = null,
    )
    {
    }
}
