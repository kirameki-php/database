<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Expressions;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

abstract class Expression
{
    /**
     * @param string $value
     * @return Raw
     */
    public static function raw(string $value): Raw
    {
        return new Raw($value);
    }

    /**
     * @param SchemaSyntax $syntax
     * @return string
     */
    abstract public function prepare(SchemaSyntax $syntax): string;
}
