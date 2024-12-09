<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Syntax\QuerySyntax;
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
    public function toValue(QuerySyntax $syntax): string
    {
        return $this->value;
    }
}
