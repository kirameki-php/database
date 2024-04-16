<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Override;
use function implode;
use function is_int;
use function strtoupper;

class MySqlSchemaSyntax extends SchemaSyntax
{
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
                $changes[] = "ALTER TABLE {$statement->table} AUTO_INCREMENT = {$column->autoIncrement}";
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
            return match ($size) {
                1 => 'TINYINT',
                2 => 'SMALLINT',
                4 => 'INT',
                8, null => 'BIGINT',
                default => throw new LogicException("Invalid int size: {$size} for {$def->name}", [
                    'column' => $def->name,
                    'size' => $size,
                ]),
            };
        }
        if ($type === 'decimal') {
            $args = Arr::without([$size, $def->scale], null);
            return 'DECIMAL' . (!empty($args) ? '(' . implode(',', $args) . ')' : '');
        }
        if ($type === 'bool') {
            return 'BOOL';
        }
        if ($type === 'datetime') {
            return 'DATETIME(' . ($size ?? 6) . ')';
        }
        if ($type === 'string') {
            return 'VARCHAR(' . ($size ?? 191) . ')';
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

        $args = Arr::without([$size, $def->scale], null);
        return strtoupper($type) . (!empty($args) ? '(' . implode(',', $args) . ')' : '');
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
    #[Override]
    public function supportsDdlTransaction(): bool
    {
        return false;
    }
}
