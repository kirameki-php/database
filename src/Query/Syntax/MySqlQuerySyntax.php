<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Functions\Syntax\MySqlFunctionSyntax;
use Kirameki\Database\Info\Statements\ColumnType;
use Kirameki\Database\Info\Statements\ListColumnsStatement;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Query\Statements\Dataset;
use Kirameki\Database\Query\Statements\NullOrder;
use Kirameki\Database\Query\Statements\Ordering;
use Kirameki\Database\Query\Statements\SelectStatement;
use Override;
use stdClass;
use function array_map;
use function implode;
use function in_array;

class MySqlQuerySyntax extends QuerySyntax
{
    use MySqlFunctionSyntax;

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatFromUseIndexPart(SelectStatement $statement): string
    {
        return $statement->forceIndex !== null
            ? "FORCE INDEX ({$this->asIdentifier($statement->forceIndex)})"
            : '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatUpsertDatasetValuesPart(Dataset $dataset, array $columns): string
    {
        return parent::formatUpsertDatasetValuesPart($dataset, $columns) . ' AS new';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatUpsertOnConflictPart(array $onConflict): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatUpsertUpdateSet(array $columns): string
    {
        $columns = $this->asIdentifiers($columns);
        $columns = array_map(static fn(string $column): string => "{$column} = new.{$column}", $columns);
        return 'ON DUPLICATE KEY UPDATE ' . $this->asCsv($columns);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatOrderByPart(?array $orderBy): string
    {
        if ($orderBy === null) {
            return '';
        }
        $clauses = [];
        foreach ($orderBy as $column => $ordering) {
            $clauses[] = $this->concat([
                $this->asIdentifier($column),
                // For MySQL, the null ordering has to come before the sort order.
                $this->formatNullOrderingPart($column, $ordering),
                $this->formatSortOrderingPart($column, $ordering),
            ]);
        }

        return "ORDER BY {$this->asCsv($clauses)}";
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatNullOrderingPart(string $column, Ordering $ordering): string
    {
        // MySql is NULL FIRST by default, so we only need to add a NULLS LAST.
        // However, MySql does not support the standard clause so we have to use
        // "$column IS NULL ASC, ..." to get the same effect.
        return $ordering->nulls === NullOrder::Last
            ? "IS NULL, {$this->asIdentifier($column)}"
            : '';
    }

    /**
     * @param ListColumnsStatement $statement
     * @return string
     */
    public function prepareTemplateForListColumns(ListColumnsStatement $statement): string
    {
        $database = $this->asLiteral($this->connectionConfig->getTableSchema());
        $table = $this->asLiteral($statement->table);
        $columns = $this->asCsv([
            "COLUMN_NAME AS `name`",
            "DATA_TYPE AS `type`",
            "COLUMN_TYPE AS `column_type`",
            "IS_NULLABLE AS `nullable`",
            "ORDINAL_POSITION AS `position`",
        ]);
        return implode(' ', [
            "SELECT {$columns} FROM INFORMATION_SCHEMA.COLUMNS",
            "WHERE TABLE_SCHEMA = {$database}",
            "AND TABLE_NAME = {$table}",
            "ORDER BY ORDINAL_POSITION ASC",
        ]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalizeListTables(stdClass $row): ?stdClass
    {
        return $row;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalizeListColumns(stdClass $row): ?stdClass
    {
        $row->type = $this->resolveColumnType($row);
        $row->nullable = $row->nullable === 'YES';
        return $row;
    }

    /**
     * @param stdClass $row
     * @return ColumnType
     */
    protected function resolveColumnType(stdClass $row): ColumnType
    {
        $type = $row->type;

        if (in_array($type, ['int', 'mediumint', 'tinyint', 'smallint', 'bigint'], true)) {
            return ColumnType::Int;
        }
        if (in_array($type, ['float', 'double'], true)) {
            return ColumnType::Float;
        }
        if ($type === 'decimal') {
            return ColumnType::Decimal;
        }
        if ($row->column_type === 'bit(1)') {
            return ColumnType::Bool;
        }
        if ($type === 'varchar') {
            return ColumnType::String;
        }
        if ($type === 'longtext') {
            return ColumnType::String;
        }
        if ($type === 'datetime') {
            return ColumnType::Timestamp;
        }
        if ($type === 'json') {
            return ColumnType::Json;
        }
        // @codeCoverageIgnoreStart
        throw new LogicException('Unsupported column type: ' . $row->type, [
            'type' => $row->type,
        ]);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForListIndexes(ListIndexesStatement $statement): string
    {
        $database = $this->asLiteral($this->connectionConfig->getTableSchema());
        $table = $this->asLiteral($statement->table);
        $columns = $this->asCsv([
            "INDEX_NAME AS `name`",
            "CASE WHEN `INDEX_NAME` = 'PRIMARY' THEN 'primary' WHEN `NON_UNIQUE` = 0 THEN 'unique' ELSE 'index' END AS `type`",
            "GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS `columns`",
        ]);
        return implode(' ', [
            "SELECT {$columns} FROM INFORMATION_SCHEMA.STATISTICS",
            "WHERE TABLE_SCHEMA = {$database}",
            "AND TABLE_NAME = {$table}",
            "GROUP BY INDEX_NAME, NON_UNIQUE",
        ]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForListForeignKeys(ListForeignKeysStatement $statement): string
    {
        $database = $this->asLiteral($this->connectionConfig->getTableSchema());
        $table = $this->asLiteral($statement->table);
        $columns = $this->asCsv([
            "t1.CONSTRAINT_NAME AS `name`",
            "GROUP_CONCAT(t1.COLUMN_NAME ORDER BY ORDINAL_POSITION) AS `columns`",
            "t1.REFERENCED_TABLE_NAME AS `referencedTable`",
            "GROUP_CONCAT(t1.REFERENCED_COLUMN_NAME ORDER BY ORDINAL_POSITION) AS `referencedColumns`",
            "t2.UPDATE_RULE AS `onUpdate`",
            "t2.DELETE_RULE AS `onDelete`",
        ]);
        return implode(' ', [
            "SELECT {$columns}",
            "FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS t1",
            "INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS t2 USING (CONSTRAINT_NAME)",
            "WHERE t1.TABLE_SCHEMA = {$database}",
            "AND t1.TABLE_NAME = {$table}",
            "AND t1.REFERENCED_TABLE_NAME IS NOT NULL",
            "GROUP BY t1.CONSTRAINT_NAME, t1.REFERENCED_TABLE_NAME, t2.UPDATE_RULE, t2.DELETE_RULE",
        ]);
    }
}
