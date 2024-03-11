<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

abstract class Expression
{
    /**
     * @param Syntax $syntax
     * @return string
     */
    abstract public function prepare(Syntax $syntax): string;

    /**
     * @return array<int, mixed>
     */
    public function getParameters(): array
    {
        return [];
    }
}
