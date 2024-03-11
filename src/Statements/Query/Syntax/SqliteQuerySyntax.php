<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Syntax;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Statements\Query\SelectStatement;

class SqliteQuerySyntax extends QuerySyntax
{
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        throw new RuntimeException('Sqlite does not support NOWAIT or SKIP LOCKED!', [
            'statement' => $statement,
        ]);
    }
}
