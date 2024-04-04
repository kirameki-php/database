<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Support\NullOrder;
use Kirameki\Database\Query\Support\Ordering;
use Override;
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
    protected function formatDatasetValuesPart(array $dataset, array $columns): string
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
        $columns = array_map(fn(string $column): string => "{$column} = new.{$column}", $columns);
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
}
