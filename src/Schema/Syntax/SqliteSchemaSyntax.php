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
use function in_array;
use function pow;
use function strtoupper;

class SqliteSchemaSyntax extends SchemaSyntax
{
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
                    'column' => $name,
                    'size' => $size,
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
        if ($type === 'uuid') {
            return "UUID_TEXT CHECK (length({$this->asIdentifier($name)}) = 36)";
        }
        if ($type === 'json') {
            return 'JSON_TEXT CHECK (json_valid(' . $this->asIdentifier($name) . '))';
        }
        if ($type === 'binary') {
            return 'BLOB';
        }
        if ($type === null) {
            throw new RuntimeException('Definition type cannot be set to null');
        }

        throw new LogicException("Unknown column type: {$type} for {$name}", [
            'column' => $name,
            'type' => $type,
        ]);
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
