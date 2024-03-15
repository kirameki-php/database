<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Expressions;

use Kirameki\Database\Statements\Schema\ColumnDefinition;
use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

class Uuid extends DefaultValue
{
    /**
     * @param SchemaSyntax $syntax
     * @return string
     */
    public function toString(SchemaSyntax $syntax): string
    {
        return $syntax->formatUuid();
    }
}
