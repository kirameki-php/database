<?php declare(strict_types=1);

namespace Kirameki\Database\Statements\Schema\Syntax;

use Kirameki\Database\Statements\Schema\TruncateTableStatement;

class SqliteSchemaSyntax extends SchemaSyntax
{
    /**
     * @param TruncateTableStatement $statement
     * @return string
     */
    public function formatTruncateTableStatement(TruncateTableStatement $statement): string
    {
        return "DELETE FROM {$this->asIdentifier($statement->table)};";
    }

    /**
     * @inheritDoc
     */
    public function supportsDdlTransaction(): bool
    {
        return true;
    }
}
