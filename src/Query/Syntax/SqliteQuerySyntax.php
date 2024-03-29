<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Query\Statements\SelectStatement;

class SqliteQuerySyntax extends QuerySyntax
{
    /**
     * @inheritDoc
     */
    protected function formatFromUseIndexPart(SelectStatement $statement): string
    {
        return $statement->forceIndex !== null
            ? "INDEXED BY {$this->asIdentifier($statement->forceIndex)}"
            : '';
    }

    /**
     * @inheritDoc
     */
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        throw new RuntimeException('Sqlite does not support NOWAIT or SKIP LOCKED!', [
            'statement' => $statement,
        ]);
    }
}
