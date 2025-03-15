<?php declare(strict_types=1);

namespace Kirameki\Database;

use Override;
use Stringable;

class Raw implements Expression
{
    /**
     * @param int|float|string $value
     */
    public function __construct(
        public readonly int|float|string|Stringable $value,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return (string) $this->value;
    }
}
