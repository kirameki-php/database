<?php declare(strict_types=1);

namespace Kirameki\Database\Expressions;

use Kirameki\Database\Expression;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Kirameki\Database\Syntax;
use Override;

/**
 * @implements Expression<SchemaSyntax>
 */
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
