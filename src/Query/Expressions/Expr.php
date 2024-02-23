<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Formatters\Formatter;

abstract class Expr
{
    /**
     * @param Formatter $formatter
     * @return string
     */
    abstract public function prepare(Formatter $formatter): string;

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [];
    }
}
