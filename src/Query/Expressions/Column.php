<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;

class Column extends Expr
{
    /**
     * @var string
     */
    public readonly string $column;

    /**
     * @param string $column
     */
    public function __construct(string $column)
    {
        $this->column = $column;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function prepare(Formatter $formatter): string
    {
        return $formatter->columnize($this->column);
    }
}
