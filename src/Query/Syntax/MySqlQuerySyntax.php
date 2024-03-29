<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Database\Query\Statements\SelectStatement;

class MySqlQuerySyntax extends QuerySyntax
{
    /**
     * @inheritDoc
     */
    protected function formatFromUseIndexPart(SelectStatement $statement): string
    {
        return $statement->forceIndex !== null
            ? "FORCE INDEX ({$this->asIdentifier($statement->forceIndex)})"
            : '';
    }
}
