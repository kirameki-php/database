<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Expressions;

use Kirameki\Database\Statements\Schema\Syntax\SchemaSyntax;

class Raw extends Expression
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
     * @param SchemaSyntax $syntax
     * @return string
     */
    public function prepare(SchemaSyntax $syntax): string
    {
        return $this->value;
    }
}
