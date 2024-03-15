<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Expressions;

use Kirameki\Database\Statements\Schema\ColumnDefinition;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

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
