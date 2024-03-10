<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Expressions;

use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\Formatters\QueryFormatter;
use Kirameki\Database\Statements\StatementFormatter;

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
     * @param StatementFormatter $formatter
     * @return string
     */
    public function prepare(StatementFormatter $formatter): string
    {
        return $this->value;
    }
}
