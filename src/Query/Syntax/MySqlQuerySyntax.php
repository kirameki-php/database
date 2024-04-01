<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Syntax;

use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpsertStatement;
use Override;
use function array_filter;
use function array_key_exists;
use function array_map;
use function count;
use function implode;

class MySqlQuerySyntax extends QuerySyntax
{
    /**
     * @inheritDoc
     */
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

}
