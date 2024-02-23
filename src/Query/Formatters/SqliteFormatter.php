<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use Kirameki\Database\Query\Statements\SelectStatement;
use RuntimeException;

class SqliteFormatter extends Formatter
{
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        throw new RuntimeException('Sqlite does not support NOWAIT or SKIP LOCKED!');
    }
}
