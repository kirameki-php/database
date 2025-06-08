<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Syntax;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Func;
use Kirameki\Core\Value;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Expression;
use Kirameki\Database\Schema\Statements\Column\AlterColumnAction;
use Kirameki\Database\Schema\Statements\Column\AlterDropColumnAction;
use Kirameki\Database\Schema\Statements\Column\AlterRenameColumnAction;
use Kirameki\Database\Schema\Statements\Column\ColumnDefinition;
use Kirameki\Database\Schema\Statements\ForeignKey\AlterDropForeignKeyAction;
use Kirameki\Database\Schema\Statements\ForeignKey\ForeignKeyConstraint;
use Kirameki\Database\Schema\Statements\Index\CreateIndexStatement;
use Kirameki\Database\Schema\Statements\Index\DropIndexStatement;
use Kirameki\Database\Schema\Statements\Table\AlterTableStatement;
use Kirameki\Database\Schema\Statements\Table\CreateTableStatement;
use Kirameki\Database\Schema\Statements\Table\DropTableStatement;
use Kirameki\Database\Schema\Statements\Table\RenameTableStatement;
use Kirameki\Database\Schema\Statements\Table\TruncateTableStatement;
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
            ...array_map($this->formatCreateIndexStatement(...), $statement->indexes),
        ];
    }

    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    protected function formatCreateTableStatement(CreateTableStatement $statement): string
    {
        return $this->concat([
            'CREATE',
            $statement->temporary ? 'TEMPORARY' : '',
            'TABLE',
            $this->asIdentifier($statement->table),
            $this->asEnclosedCsv(array_filter([
                ...array_map($this->formatColumnDefinition(...), $statement->columns),
                $this->formatCreateTablePrimaryKeyPart($statement),
                ...$this->formatCreateTableForeignKeyParts($statement),
            ])),
        ]);
    }

    /**
     * @param CreateTableStatement $statement
     * @return string
     */
    protected function formatCreateTablePrimaryKeyPart(CreateTableStatement $statement): ?string
    {
        $columns = $statement->primaryKey?->columns;

        if ($columns === null) {
            return null;
        }

        $pkParts = [];
        foreach ($columns as $column => $order) {
            $pkParts[] = "{$this->asIdentifier($column)} {$order->value}";
        }
        if ($pkParts !== []) {
            return 'PRIMARY KEY ' . $this->asEnclosedCsv($pkParts);
        }

        throw new LogicException('Primary key must have at least one column defined.', [
            'statement' => $statement,
        ]);
    }

    /**
     * @param CreateTableStatement $statement
     * @return list<string>
     */
    protected function formatCreateTableForeignKeyParts(CreateTableStatement $statement): array
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
        return $this->concat([
            $action->type->value,
            'COLUMN',
            $this->formatColumnDefinition($action->definition),
        ]);
    }

    /**
     * @param AlterDropColumnAction $action
     * @return string
     */
    protected function formatDropColumnAction(AlterDropColumnAction $action): string
    {
        if ($this->databaseConfig->dropProtection) {
            $database = $this->connectionConfig->getDatabaseName();
            throw new DropProtectionException("Dropping columns is prohibited in database '{$database}'.", [
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
        $renames = [];
        foreach ($statement->definitions as $definition) {
            $from = $this->asIdentifier($definition->from);
            $to = $this->asIdentifier($definition->to);
            $renames[] = "ALTER TABLE {$from} RENAME TO {$to}";
        }
        return $renames;
    }

    /**
     * @param DropTableStatement $statement
     * @return list<string>
     */
    public function compileDropTable(DropTableStatement $statement): array
    {
        if ($this->databaseConfig->dropProtection) {
            throw new DropProtectionException("Dropping tables are prohibited.", [
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
        return $this->concat([
            'CREATE',
            $statement->unique ? 'UNIQUE' : '',
            'INDEX',
            $this->asIdentifier($statement->name ?? $this->generateIndexNameFromColumns($statement)),
            'ON',
            $this->asIdentifier($statement->table),
            $this->formatCreateIndexColumnsPart($statement),
        ]);
    }

    /**
     * @param CreateIndexStatement $index
     * @return string
     */
    protected function generateIndexNameFromColumns(CreateIndexStatement $index): string
    {
        return implode('_', array_merge([$index->table], array_keys($index->columns)));
    }

    /**
     * @param CreateIndexStatement $index
     * @return string
     */
    protected function formatCreateIndexColumnsPart(CreateIndexStatement $index): string
    {
        $columnParts = [];
        foreach ($index->columns as $column => $order) {
            $columnParts[] = "$column {$order->value}";
        }
        return $this->asEnclosedCsv($columnParts);
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

        if ($value instanceof Expression) {
            return $value->toValue($this);
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
     * @param TruncateTableStatement $statement
     * @return list<string>
     */
    public function compileTruncateTable(TruncateTableStatement $statement): array
    {
        if ($this->databaseConfig->dropProtection) {
            $database = $this->connectionConfig->getDatabaseName();
            throw new DropProtectionException("TRUNCATE is prohibited in database '{$database}'.", [
                'statement' => $statement,
            ]);
        }

        return $this->formatTruncateStatement($statement);
    }

    /**
     * @param TruncateTableStatement $statement
     * @return list<string>
     */
    abstract protected function formatTruncateStatement(TruncateTableStatement $statement): array;

    /**
     * @return bool
     */
    abstract public function supportsDdlTransaction(): bool;
}
