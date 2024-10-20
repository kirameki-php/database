<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Value;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Schema\Expressions\DefaultValue;
use Kirameki\Database\Schema\Statements\AlterColumnAction;
use Kirameki\Database\Schema\Statements\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\AlterDropForeignKeyAction;
use Kirameki\Database\Schema\Statements\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\AlterTableStatement;
use Kirameki\Database\Schema\Statements\ColumnDefinition;
use Kirameki\Database\Schema\Statements\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\CreateTableStatement;
use Kirameki\Database\Schema\Statements\DropIndexStatement;
use Kirameki\Database\Schema\Statements\DropTableStatement;
use Kirameki\Database\Schema\Statements\ForeignKeyConstraint;
use Kirameki\Database\Schema\Statements\RenameTableStatement;
use Kirameki\Database\Schema\Statements\TruncateTableStatement;
use Kirameki\Database\Syntax;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function implode;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

abstract class SchemaSyntax extends Syntax
{
    /**
     * @param CreateTableStatement $statement
     * @return list<string>
     */
    public function compileCreateTable(CreateTableStatement $statement): array
    {
        return [
            $this->formatCreateTableStatement($statement),
        ];
    }

    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    protected function formatCreateTableStatement(CreateTableStatement $statement): string
    {
        $parts = [];
        $parts[] = 'CREATE';
        if ($statement->temporary) {
            $parts[] = 'TEMPORARY';
        }
        $parts[] = 'TABLE';
        $parts[] = $this->asIdentifier($statement->table);
        $subParts = array_map($this->formatColumnDefinition(...), $statement->columns);
        $subParts[] = $this->formatCreateTablePrimaryKeyPart($statement);
        $subParts[] = $this->formatCreateTableIndexParts($statement);
        $subParts[] = $this->formatCreateTableForeignKeyParts($statement);
        $parts[] = $this->asEnclosedCsv(array_filter(Arr::flatten($subParts), fn($v) => $v !== null));
        return implode(' ', $parts);
    }

    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    protected function formatCreateTablePrimaryKeyPart(CreateTableStatement $statement): ?string
    {
        $pkParts = [];
        foreach ($statement->primaryKey?->columns ?? [] as $column => $order) {
            $pkParts[] = "$column $order";
        }
        if ($pkParts !== []) {
            return 'PRIMARY KEY (' . implode(', ', $pkParts) . ')';
        }
        return null;
    }

    /**
     * @param CreateTableStatement $statement
     * @return list<string>
     */
    protected function formatCreateTableIndexParts(CreateTableStatement $statement): array
    {
        return array_map(function(CreateIndexStatement $index) {
            $parts = [];
            if ($index->unique) {
                $parts[] = 'UNIQUE';
            }
            $parts[] = 'INDEX';
            $columnParts = [];
            foreach ($index->columns as $column => $order) {
                $columnParts[] = "$column $order";
            }
            $parts[] = $this->asEnclosedCsv($columnParts);
            return implode(' ', $parts);
        }, $statement->indexes);
    }

    /**
     * @param CreateTableStatement $statement
     * @return list<string>
     */
    public function formatCreateTableForeignKeyParts(CreateTableStatement $statement): array
    {
        return array_map($this->formatForeignKeyConstraint(...), $statement->foreignKeys);
    }

    /**
     * @param AlterTableStatement $statement
     * @return list<string>
     */
    public function compileAlterTable(AlterTableStatement $statement): array
    {
        $statements = array_map(fn(object $action) => match (true) {
            $action instanceof AlterColumnAction => $this->formatAlterColumnAction($action),
            $action instanceof AlterDropColumnAction => $this->formatDropColumnAction($action),
            $action instanceof AlterRenameColumnAction => $this->formatRenameColumnAction($action),
            $action instanceof CreateIndexStatement => $this->compileCreateIndex($action),
            $action instanceof DropIndexStatement => $this->compileDropIndex($action),
            $action instanceof ForeignKeyConstraint => $this->formatAddForeignKeyAction($action),
            $action instanceof AlterDropForeignKeyAction => $this->formatDropForeignKeyAction($action),
            default => throw new InvalidTypeException('Unsupported action type: ' . $action::class),
        }, $statement->actions);
        return Arr::flatten($statements);
    }

    /**
     * @param AlterColumnAction $action
     * @return string
     */
    protected function formatAlterColumnAction(AlterColumnAction $action): string
    {
        $parts = [];
        $parts[] = $action->type->value;
        $parts[] = 'COLUMN';
        $parts[] = $this->formatColumnDefinition($action->definition);
        $parts[] = $action->positionType;
        $parts[] = $action->positionColumn;
        return implode(' ', array_filter($parts));
    }

    /**
     * @param AlterDropColumnAction $action
     * @return string
     */
    protected function formatDropColumnAction(AlterDropColumnAction $action): string
    {
        if ($this->databaseConfig->dropProtection) {
            throw new DropProtectionException('Dropping columns are prohibited by configuration.', [
                'action' => $action,
            ]);
        }

        $parts = [];
        $parts[] = 'DROP COLUMN';
        $parts[] = $this->asIdentifier($action->column);
        return implode(' ', $parts);
    }

    /**
     * @param AlterRenameColumnAction $action
     * @return string
     */
    protected function formatRenameColumnAction(AlterRenameColumnAction $action): string
    {
        $parts = [];
        $parts[] = 'RENAME COLUMN';
        $parts[] = $this->asIdentifier($action->from);
        $parts[] = 'TO';
        $parts[] = $this->asIdentifier($action->to);
        return implode(' ', $parts);
    }

    /**
     * @param RenameTableStatement $statement
     * @return list<string>
     */
    public function compileRenameTable(RenameTableStatement $statement): array
    {
        return [
            "ALTER TABLE {$this->asIdentifier($statement->from)} RENAME TO {$this->asIdentifier($statement->to)}",
        ];
    }

    /**
     * @param DropTableStatement $statement
     * @return list<string>
     */
    public function compileDropTable(DropTableStatement $statement): array
    {
        if ($this->databaseConfig->dropProtection) {
            throw new DropProtectionException('Dropping tables are prohibited by configuration.', [
                'statement' => $statement,
            ]);
        }

        return [
            "DROP TABLE {$this->asIdentifier($statement->table)}",
        ];
    }

    /**
     * @param CreateIndexStatement $statement
     * @return list<string>
     */
    public function compileCreateIndex(CreateIndexStatement $statement): array
    {
        return [
            $this->formatCreateIndexStatement($statement),
        ];
    }

    /**
     * @param CreateIndexStatement $statement
     * @return string
     */
    protected function formatCreateIndexStatement(CreateIndexStatement $statement): string
    {
        $parts = [];
        $parts[] = 'CREATE';
        if ($statement->unique) {
            $parts[] = 'UNIQUE';
        }
        $parts[] = 'INDEX';
        $parts[] = $statement->name ?? implode('_', array_merge([$statement->table], array_keys($statement->columns)));
        $parts[] = 'ON';
        $parts[] = $statement->table;
        $columnParts = [];
        foreach ($statement->columns as $column => $order) {
            $columnParts[] = "$column $order";
        }
        $parts[] = $this->asEnclosedCsv($columnParts);
        return implode(' ', $parts);
    }

    /**
     * @param DropIndexStatement $statement
     * @return list<string>
     */
    public function compileDropIndex(DropIndexStatement $statement): array
    {
        $name = $statement->name ?? implode('_', array_merge([$statement->table], $statement->columns));
        return [
            "DROP INDEX {$this->asIdentifier($name)} ON {$this->asIdentifier($statement->table)}",
        ];
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    protected function formatColumnDefinition(ColumnDefinition $def): string
    {
        $parts = [];
        $parts[] = $this->asIdentifier($def->name);
        $parts[] = $this->formatColumnType($def);
        if ($def->nullable === false) {
            $parts[] = 'NOT NULL';
        }
        if ($def->default !== null) {
            $parts[] = 'DEFAULT ' . $this->formatDefaultValue($def);
        }
        if ($def->primaryKey === true) {
            $parts[] = 'PRIMARY KEY';
        }
        if ($def->references !== null) {
            $parts[] = $this->formatReferencesClause($def->references);
        }
        return implode(' ', $parts);
    }

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    abstract protected function formatColumnType(ColumnDefinition $def): string;

    /**
     * @param ColumnDefinition $def
     * @return string
     */
    protected function formatDefaultValue(ColumnDefinition $def): string
    {
        $value = $def->default;

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_string($value)) {
            return $this->asLiteral($value);
        }

        if ($value instanceof DefaultValue) {
            return $value->toString($this);
        }

        throw new LogicException('Unknown default value type: ' . Value::getType($value), [
            'value' => $value,
            'column' => $def->name,
        ]);
    }

    /**
     * @param ForeignKeyConstraint $constraint
     * @return string
     */
    protected function formatForeignKeyConstraint(ForeignKeyConstraint $constraint): string
    {
        $parts = [];
        if ($constraint->name !== null) {
            $parts[] = 'CONSTRAINT';
            $parts[] = $this->asIdentifier($constraint->name);
        }
        $parts[] = 'FOREIGN KEY';
        $parts[] = $this->asEnclosedCsv($this->asIdentifiers($constraint->columns));
        $parts[] = $this->formatReferencesClause($constraint);
        return implode(' ', $parts);
    }

    protected function formatAddForeignKeyAction(ForeignKeyConstraint $action): string
    {
        return 'ADD ' . $this->formatForeignKeyConstraint($action);
    }

    protected function formatDropForeignKeyAction(AlterDropForeignKeyAction $action): string
    {
        $parts = [];
        $parts[] = 'DROP FOREIGN KEY';
        $parts[] = $this->asIdentifier($action->name);
        return implode(' ', $parts);
    }

    /**
     * @param ForeignKeyConstraint $constraint
     * @return string
     */
    protected function formatReferencesClause(ForeignKeyConstraint $constraint): string
    {
        $parts = [];
        $parts[] = 'REFERENCES';
        $parts[] = $this->asIdentifier($constraint->referencedTable);
        $parts[] = $this->asEnclosedCsv($this->asIdentifiers($constraint->referencedColumns));
        if ($constraint->onDelete !== null) {
            $parts[] = 'ON DELETE ' . $constraint->onDelete->value;
        }
        if ($constraint->onUpdate !== null) {
            $parts[] = 'ON UPDATE ' . $constraint->onUpdate->value;
        }
        return implode(' ', $parts);
    }

    /**
     * @param int|null $size
     * @return string
     */
    public function formatCurrentTimestamp(?int $size = null): string
    {
        return 'CURRENT_TIMESTAMP' . ($size ? '(' . $size . ')' : '');
    }

    /**
     * @return string
     */
    public function formatUuid(): string
    {
        return 'UUID()';
    }

    /**
     * @param TruncateTableStatement $statement
     * @return list<string>
     */
    public function compileTruncateTable(TruncateTableStatement $statement): array
    {
        if ($this->databaseConfig->dropProtection) {
            throw new DropProtectionException('TRUNCATE are prohibited by configuration.', [
                'statement' => $statement,
            ]);
        }

        return [
            "TRUNCATE TABLE {$this->asIdentifier($statement->table)}",
        ];
    }

    /**
     * @return bool
     */
    abstract public function supportsDdlTransaction(): bool;
}
