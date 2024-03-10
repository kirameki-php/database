<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Query\Formatters;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Statements\Query\SelectStatement;

class SqliteQueryFormatter extends QueryFormatter
{
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        throw new RuntimeException('Sqlite does not support NOWAIT or SKIP LOCKED!', [
            'statement' => $statement,
        ]);
    }
}
