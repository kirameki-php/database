<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Functions\Syntax\SqliteFunctionSyntax;
use Kirameki\Database\Schema\Statements\Column\ColumnDefinition;
use Kirameki\Database\Schema\Statements\Index\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\Index\DropIndexStatement;
use Kirameki\Database\Schema\Statements\Table\CreateTableStatement;
use Kirameki\Database\Schema\Statements\Table\TruncateTableStatement;
use Override;
use function array_keys;
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
    protected function formatCreateTableStatement(CreateTableStatement $statement): string
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
    protected function formatColumnDefinition(ColumnDefinition $def): string
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
    protected function formatCreateTablePrimaryKeyPart(CreateTableStatement $statement): ?string
    {
        if ($statement->primaryKey?->columns === null) {
            return null;
        }

        $pkParts = array_keys($statement->primaryKey->columns);
        if ($pkParts !== []) {
            return 'PRIMARY KEY ' . $this->asEnclosedCsv($this->asIdentifiers($pkParts));
        }

        throw new LogicException('Primary key must have at least one column defined.', [
            'statement' => $statement,
        ]);
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
            if (!in_array($size, [1, 2, 4], true)) {
                throw new LogicException("\"{$name}\" has an invalid integer size: {$size}. Only 1, 2, 4, 8 are supported.", [
                    'definition' => $def,
                ]);
            }
            $limit = pow(2, 8 * $size);
            $min = $limit * -1;
            $max = $limit - 1;
            $ddl .= " CHECK ({$this->asIdentifier($name)} BETWEEN {$min} AND {$max})";
            return $ddl;
        }
        if ($type === 'float') {
            if ($size === null || $size === 8) {
                return 'REAL';
            }
            throw new LogicException("\"{$name}\" has invalid float size: {$size}. Sqlite only supports 8 (REAL).", [
                'definition' => $def,
            ]);
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
        if ($type === 'timestamp') {
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
    public function compileDropIndex(DropIndexStatement $statement): array
    {
        $table = $statement->table;
        $columns = $statement->columns;
        $name = $statement->name ?? $this->generateIndexNameFromColumns($table, $columns);

        return [
            "DROP INDEX {$this->asIdentifier($name)}",
        ];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatTruncateStatement(TruncateTableStatement $statement): array
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
