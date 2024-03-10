<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

abstract class Expression
{
    /**
     * @param StatementFormatter $formatter
     * @return string
     */
    abstract public function prepare(StatementFormatter $formatter): string;

    /**
     * @return array<int, mixed>
     */
    public function getParameters(): array
    {
        return [];
    }
}
