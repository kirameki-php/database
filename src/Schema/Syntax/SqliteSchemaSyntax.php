<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;

class SqliteSchemaSyntax extends SchemaSyntax
{
    /**
     * @param ColumnDefinition $def
     * @return string
     */
    public function formatColumnDefinition(ColumnDefinition $def): string
    {
        $string = parent::formatColumnDefinition($def);

        if ($def->autoIncrement) {
            $string .= ' AUTOINCREMENT';
        }

        return $string;
    }

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
