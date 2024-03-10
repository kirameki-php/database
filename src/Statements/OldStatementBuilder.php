<?php declare(strict_types=1);

namespace Kirameki\Database\Statements;

interface OldStatementBuilder
{
    /**
     * @return list<string>
     */
    public function build(): array;
}
