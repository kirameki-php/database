<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Functions\Syntax\MySqlFunctionSyntax;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
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
                default => throw new LogicException("Invalid int size: {$size} for {$def->name}", [
                    'column' => $def->name,
                    'size' => $size,
                ]),
            };
        }
        if ($type === 'decimal') {
            $args = array_filter([$size, $def->scale], static fn($arg) => $arg !== null);
            return 'DECIMAL' . (!empty($args) ? $this->asEnclosedCsv($args) : '');
        }
        if ($type === 'bool') {
            return 'BOOL';
        }
        if ($type === 'datetime') {
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
            throw new LogicException('Definition type cannot be set to null', [
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
    public function formatColumnDefinition(ColumnDefinition $def): string
    {
        $parts = [];
        $parts[] = parent::formatColumnDefinition($def);

        if ($def->autoIncrement !== null) {
            $parts[] = 'AUTO_INCREMENT';
        }

        return implode(' ', $parts);
    }

    /**
     * @inheritDoc
     */
    public function formatCreateTableForeignKeyParts(CreateTableStatement $statement): array
    {
        return array_map($this->formatForeignKeyConstraint(...), $statement->foreignKeys);
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
