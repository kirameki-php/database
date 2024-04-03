<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Support\NullOrder;
use Kirameki\Database\Query\Support\Ordering;
use Override;

class SqliteQuerySyntax extends QuerySyntax
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatFromUseIndexPart(SelectStatement $statement): string
    {
        return $statement->forceIndex !== null
            ? "INDEXED BY {$this->asIdentifier($statement->forceIndex)}"
            : '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        throw new RuntimeException('Sqlite does not support NOWAIT or SKIP LOCKED!', [
            'statement' => $statement,
        ]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatNullOrderingPart(string $column, Ordering $ordering): string
    {
        // Sqlite is NULL FIRST by default, so we only need to add NULLS LAST
        return match ($ordering->nulls) {
            NullOrder::First, null => '',
            NullOrder::Last => 'NULLS LAST',
        };
    }
}
