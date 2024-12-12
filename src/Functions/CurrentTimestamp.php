<?php declare(strict_types=1);

namespace Kirameki\Database\Functions;

use Kirameki\Database\Expression;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Syntax;
use Override;

class CurrentTimestamp implements Expression
{
    /**
     * @param ColumnDefinition $definition
     */
    public function __construct(
        protected ColumnDefinition $definition,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function toValue(Syntax $syntax): string
    {
        return $syntax->formatCurrentTimestamp($this->definition->size);
    }
}
