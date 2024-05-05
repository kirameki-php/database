<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Iterator;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Info\Statements\ListForeignKeysStatement;
use Kirameki\Database\Info\Statements\ListIndexesStatement;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Support\Dataset;
use Kirameki\Database\Query\Support\NullOrder;
use Kirameki\Database\Query\Support\Ordering;
use Override;
use stdClass;
use function array_map;
use function implode;

class MySqlQuerySyntax extends QuerySyntax
{
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
    protected function formatDatasetValuesPart(Dataset $dataset, array $columns): string
    {
        return parent::formatDatasetValuesPart($dataset, $columns) . 'AS new';
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
        $columns = array_map($this->asIdentifier(...), $columns);
        $columns = array_map(static fn(string $column): string => "{$column} = new.{$column}", $columns);
        return 'ON DUPLICATE KEY UPDATE' . implode(', ', $columns);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function formatOrderByPart(ConditionsStatement $statement): string
    {
        if ($statement->orderBy === null) {
            return '';
        }
        $clauses = [];
        foreach ($statement->orderBy as $column => $ordering) {
            $clauses[] = implode(' ', [
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
            ? " IS NULL, {$this->asIdentifier($column)}"
            : '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function normalizeListColumns(iterable $rows): Iterator
    {
        foreach ($rows as $row) {
            $row->type = match ($row->type) {
                'int', 'mediumint', 'tinyint', 'smallint', 'bigint' => 'integer',
                'decimal', 'float', 'double' => 'float',
                'bool' => 'bool',
                'varchar' => 'string',
                'datetime' => 'datetime',
                'json' => 'json',
                'blob' => 'binary',
                default => throw new LogicException('Unsupported column type: ' . $row->type, [
                    'type' => $row->type,
                ]),
            };
            $row->nullable = $row->nullable === 'YES';
            yield $row;
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function prepareTemplateForListIndexes(ListIndexesStatement $statement): string
    {
        $database = $this->asLiteral($this->config->getTableSchema());
        $table = $this->asLiteral($statement->table);
        $columns = implode(', ', [
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
        $database = $this->asLiteral($this->config->getTableSchema());
        $table = $this->asLiteral($statement->table);
        $columns = implode(', ', [
            "CONSTRAINT_NAME AS `name`",
            "GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) AS `columns`",
            "REFERENCED_TABLE_NAME AS `referencedTable`",
            "GROUP_CONCAT(REFERENCED_COLUMN_NAME ORDER BY ORDINAL_POSITION) AS `referencedColumns`",
        ]);
        return implode(' ', [
            "SELECT {$columns} FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE",
            "WHERE TABLE_SCHEMA = {$database}",
            "AND TABLE_NAME = {$table}",
            "AND REFERENCED_TABLE_NAME IS NOT NULL",
            "GROUP BY CONSTRAINT_NAME, REFERENCED_TABLE_NAME",
        ]);
    }
}
