<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\PrimaryKeyConstraint;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use function array_keys;
use function implode;
use function strtoupper;

class SqliteSchemaSyntax extends SchemaSyntax
{
    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    public function formatCreateTableStatement(CreateTableStatement $statement): string
    {
        return parent::formatCreateTableStatement($statement) . ' STRICT';
    }

    /**
     * @param PrimaryKeyConstraint $constraint
     * @return string
     */
    public function formatCreateTablePrimaryKeyPart(PrimaryKeyConstraint $constraint): string
    {
        $pkParts = array_keys($constraint->columns);
        if ($pkParts !== []) {
            return 'PRIMARY KEY (' . implode(', ', $pkParts) . ')';
        }
        return '';
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    protected function formatColumnType(ColumnDefinition $def): string
    {
        $type = $def->type;

        if ($type === 'int') {
            return 'INT';
        }
        if ($type === 'float') {
            return 'REAL';
        }
        if ($type === 'decimal') {
            return 'NUMERIC';
        }
        if ($type === 'bool') {
            return 'INT';
        }
        if ($type === 'datetime') {
            return 'TEXT';
        }
        if ($type === 'string') {
            return 'TEXT';
        }
        if ($type === 'uuid') {
            return 'TEXT';
        }
        if ($type === null) {
            throw new RuntimeException('Definition type cannot be set to null');
        }

        $args = Arr::without([$def->size, $def->scale], null);
        return strtoupper($type) . (!empty($args) ? '(' . implode(',', $args) . ')' : '');
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
