<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Expressions;

use Kirameki\Database\Statements\Expression;
use Kirameki\Database\Statements\Query\Formatters\QueryFormatter;

class Column extends Expression
{
    /**
     * @param string $column
     */
    public function __construct(
        public readonly string $column,
    )
    {
    }

    /**
     * @param QueryFormatter $formatter
     * @return string
     */
    public function prepare(QueryFormatter $formatter): string
    {
        return $formatter->asColumn($this->column);
    }
}
