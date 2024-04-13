<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;
use Override;

class CurrentTimestamp extends DefaultValue
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
    public function toString(SchemaSyntax $syntax): string
    {
        return $syntax->formatCurrentTimestamp($this->definition->size);
    }
}
