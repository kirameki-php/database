<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Expressions;

use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\Syntax\QuerySyntax;
use Kirameki\Database\Statements\Syntax;

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
     * @param Syntax $syntax
     * @return string
     */
    public function prepare(Syntax $syntax): string
    {
        return $this->value;
    }
}
