<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Functions\Syntax\SqliteFunctionSyntax;
use Kirameki\Database\Schema\Statements\Column\ColumnDefinition;
use Kirameki\Database\Schema\Statements\Table\CreateTableStatement;
use Kirameki\Database\Schema\Statements\Table\TruncateTableStatement;
use Override;
use function array_keys;
use function implode;
use function in_array;
use function is_int;
use function pow;

class SqliteSchemaSyntax extends SchemaSyntax
{
    use SqliteFunctionSyntax;

    /**
     * @inheritDoc
     */
    #[Override]
    public function compileCreateTable(CreateTableStatement $statement): array
    {
        return [
            ...parent::compileCreateTable($statement),
            ...$this->addAutoIncrementStartingValue($statement),
        ];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCreateTableStatement(CreateTableStatement $statement): string
    {
        $formatted = parent::formatCreateTableStatement($statement);

        $hasAutoIncrementColumn = false;
        foreach ($statement->columns as $column) {
            if ($column->autoIncrement !== null) {
                $hasAutoIncrementColumn = true;
                break;
            }
        }
        if (!$hasAutoIncrementColumn) {
            $formatted .= ' WITHOUT ROWID';
        }

        return $formatted;
    }

    /**
     * @param CreateTableStatement $statement
     * @return list<string>
     */
    protected function addAutoIncrementStartingValue(CreateTableStatement $statement): array
    {
        $changes = [];
        foreach ($statement->columns as $column) {
            if (is_int($column->autoIncrement)) {
                $seq = $column->autoIncrement;
                $name = $this->asLiteral($statement->table);
                $changes[] = "UPDATE \"sqlite_sequence\" SET \"seq\" = {$seq} WHERE \"name\" = {$name}";
            }
        }
        return $changes;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatColumnDefinition(ColumnDefinition $def): string
    {
        $formatted = parent::formatColumnDefinition($def);

        if ($def->autoIncrement !== null) {
            if (!$def->primaryKey) {
                throw new LogicException('Auto increment column must be the primary key.');
            }
            $formatted .= ' AUTOINCREMENT';
        }

        return $formatted;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function formatCreateTablePrimaryKeyPart(CreateTableStatement $statement): ?string
    {
        if ($statement->primaryKey?->columns === null) {
            return null;
        }

        $pkParts = array_keys($statement->primaryKey->columns);
        if ($pkParts !== []) {
            return 'PRIMARY KEY (' . implode(', ', $pkParts) . ')';
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatColumnType(ColumnDefinition $def): string
    {
        $name = $def->name;
        $type = $def->type;
        $size = $def->size;

        if ($type === 'int') {
            $ddl = 'INTEGER';
            $size ??= 8;
            if ($size === 8) {
                return $ddl;
            }
            if (!in_array($size, [2, 4], true)) {
                throw new LogicException("Invalid int size for {$name}. Expected: [2, 4, 8]. Got: {$size}.", [
                    'definition' => $def,
                ]);
            }
            $min = pow(-2, 8 * $size);
            $max = pow(2, 8 * $size) - 1;
            $ddl .= " CHECK ({$this->asIdentifier($name)} BETWEEN {$min} AND {$max})";
            return $ddl;
        }
        if ($type === 'float') {
            return 'REAL';
        }
        if ($type === 'decimal') {
            return 'NUMERIC';
        }
        if ($type === 'bool') {
            return "BOOLEAN CHECK ({$this->asIdentifier($name)} IN (TRUE, FALSE))";
        }
        if ($type === 'string') {
            $ddl = 'TEXT';
            if ($size !== null) {
                $ddl .= " CHECK (length({$this->asIdentifier($name)}) <= $size)";
            }
            return $ddl;
        }
        if ($type === 'text') {
            return 'TEXT';
        }
        if ($type === 'uuid') {
            return "TEXT CHECK (length({$this->asIdentifier($name)}) = 36)";
        }
        if ($type === 'json') {
            return 'JSON_TEXT CHECK (json_valid(' . $this->asIdentifier($name) . '))';
        }
        if ($type === 'datetime') {
            return 'DATETIME CHECK (datetime(' . $this->asIdentifier($name) . ') IS NOT NULL)';
        }
        if ($type === 'binary') {
            return 'BLOB';
        }
        if ($type === null) {
            throw new LogicException('Definition type cannot be set to null', [
                'definition' => $def,
            ]);
        }

        throw new LogicException("Unknown column type: {$type} for {$name}", [
            'column' => $name,
            'type' => $type,
        ]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function compileTruncateTable(TruncateTableStatement $statement): array
    {
        $statements = [];
        $statements[] = 'DELETE FROM "sqlite_sequence" WHERE "name" = ' . $this->asLiteral($statement->table);
        $statements[] = "DELETE FROM {$this->asIdentifier($statement->table)}";
        return $statements;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function supportsDdlTransaction(): bool
    {
        return true;
    }
}
