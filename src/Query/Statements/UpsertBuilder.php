<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\QueryHandler;
use function array_is_list;
use function array_values;

/**
 * @extends QueryBuilder<UpsertStatement>
 */
class UpsertBuilder extends QueryBuilder
{
    /**
     * @param QueryHandler $handler
     * @param string $table
     */
    public function __construct(QueryHandler $handler, string $table)
    {
        parent::__construct($handler, new UpsertStatement($table, new Dataset()));
    }

    /**
     * @param iterable<string, mixed> $data
     * @return $this
     */
    public function value(iterable $data): static
    {
        return $this->values([$data]);
    }

    /**
     * @param iterable<int, iterable<string, mixed>> $dataset
     * @return $this
     */
    public function values(iterable $dataset): static
    {
        $this->statement->dataset->merge($dataset);
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function onConflict(string ...$columns): static
    {
        $this->statement->onConflict = array_is_list($columns) ? $columns : array_values($columns);
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function returning(string ...$columns): static
    {
        $this->statement->returning = array_is_list($columns) ? $columns : array_values($columns);
        return $this;
    }
}
