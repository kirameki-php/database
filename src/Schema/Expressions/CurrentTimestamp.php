<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Expressions;

use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Syntax\SchemaSyntax;

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
     * @param SchemaSyntax $syntax
     * @return string
     */
    public function toString(SchemaSyntax $syntax): string
    {
        return $syntax->formatCurrentTimestamp($this->definition->size);
    }
}
