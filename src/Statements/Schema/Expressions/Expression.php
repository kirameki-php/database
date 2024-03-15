<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Expressions;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

abstract class Expression
{
    /**
     * @param SchemaSyntax $syntax
     * @return string
     */
    abstract public function toString(SchemaSyntax $syntax): string;
}
