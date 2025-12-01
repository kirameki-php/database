<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Exceptions\LogicException;
use Kirameki\Exceptions\NotSupportedException;
use Kirameki\Database\Expression;
use Kirameki\Database\Functions\Syntax\MySqlFunctionSyntax;
use Kirameki\Database\Schema\Statements\Column\ColumnDefinition;
use Kirameki\Database\Schema\Statements\Table\CreateTableStatement;
use Kirameki\Database\Schema\Statements\Table\RenameTableStatement;
use Kirameki\Database\Schema\Statements\Table\TruncateTableStatement;
use Override;
use function array_filter;
use function array_map;
use function implode;
use function is_int;
use function strtoupper;

class MySqlSchemaSyntax extends SchemaSyntax
{
    use MySqlFunctionSyntax;

    public const int DEFAULT_INT_SIZE = 8;

    public const int DEFAULT_FLOAT_SIZE = 8;

    public const int DEFAULT_DECIMAL_SIZE = 65;

    public const int DEFAULT_DECIMAL_SCALE = 30;

    public const int DEFAULT_STRING_SIZE = 191;

    public const int DEFAULT_TIME_PRECISION = 6;

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
     * @param CreateTableStatement $statement
     * @return list<string>
     */
    protected function addAutoIncrementStartingValue(CreateTableStatement $statement): array
    {
        $changes = [];
        foreach ($statement->columns as $column) {
            if (is_int($column->autoIncrement)) {
                $changes[] = "ALTER TABLE {$this->asIdentifier($statement->table)} AUTO_INCREMENT = {$column->autoIncrement}";
            }
        }
        return $changes;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatColumnType(ColumnDefinition $def): string
    {
        $type = $def->type;
        $size = $def->size;

        if ($type === 'int') {
            return match ($size ?? self::DEFAULT_INT_SIZE) {
                1 => 'TINYINT',
                2 => 'SMALLINT',
                4 => 'INT',
                8 => 'BIGINT',
                default => throw new NotSupportedException("\"{$def->name}\" has an invalid integer size: {$size}. MySQL only supports 1 (TINYINT), 2 (SMALLINT), 4 (INT), and 8 (BIGINT).", [
                    'column' => $def->name,
                    'size' => $size,
                ]),
            };
        }
        if ($type === 'float') {
            return match ($size ?? self::DEFAULT_FLOAT_SIZE) {
                4 => 'FLOAT',
                8 => 'DOUBLE',
                default => throw new NotSupportedException("\"{$def->name}\" has an invalid float size: {$size}. MySQL only supports 4 (FLOAT) and 8 (DOUBLE).", [
                    'column' => $def->name,
                    'size' => $size,
                ]),
            };
        }
        if ($type === 'decimal') {
            return 'DECIMAL' . ($this->asEnclosedCsv([
                $size ?? self::DEFAULT_DECIMAL_SIZE,
                $def->scale ?? self::DEFAULT_DECIMAL_SCALE,
            ]));
        }
        if ($type === 'bool') {
            return 'BIT(1)';
        }
        if ($type === 'timestamp') {
            return 'DATETIME(' . ($size ?? self::DEFAULT_TIME_PRECISION) . ')';
        }
        if ($type === 'string') {
            return 'VARCHAR(' . ($size ?? self::DEFAULT_STRING_SIZE) . ')';
        }
        if ($type === 'text') {
            return 'LONGTEXT';
        }
        if ($type === 'uuid') {
            return 'VARCHAR(36)';
        }
        if ($type === null) {
            throw new LogicException('Definition type cannot be set to null.', [
                'definition' => $def,
            ]);
        }

        $args = array_filter([$size, $def->scale], static fn($arg) => $arg !== null);
        return strtoupper($type) . (!empty($args) ? $this->asEnclosedCsv($args) : '');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatColumnDefinition(ColumnDefinition $def): string
    {
        $parts = [];
        $parts[] = parent::formatColumnDefinition($def);

        if ($def->autoIncrement !== null) {
            if (!$def->primaryKey) {
                throw new NotSupportedException('Auto-increment must be defined on primary key columns.', [
                    'definition' => $def,
                ]);
            }
            $parts[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $parts);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatCreateTableForeignKeyParts(CreateTableStatement $statement): array
    {
        return array_map($this->formatForeignKeyConstraint(...), $statement->foreignKeys);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function compileRenameTable(RenameTableStatement $statement): array
    {
        $parts = [];
        foreach ($statement->definitions as $def) {
            $from = $this->asIdentifier($def->from);
            $to = $this->asIdentifier($def->to);
            $parts[] = "{$from} TO {$to}";
        }
        return [
            "RENAME TABLE {$this->asCsv($parts)}",
        ];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatTruncateStatement(TruncateTableStatement $statement): array
    {
        return [
            "TRUNCATE TABLE {$this->asIdentifier($statement->table)}",
        ];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function supportsDdlTransaction(): bool
    {
        return false;
    }
}
