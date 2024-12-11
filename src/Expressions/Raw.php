<?php declare(strict_types=1);

namespace Kirameki\Database\Expressions;

use Kirameki\Database\Syntax;
use Override;

class Raw implements Expression
{
    /**
     * @param string $value
     */
    public function __construct(
        public readonly string $value,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $this->value;
    }
}
