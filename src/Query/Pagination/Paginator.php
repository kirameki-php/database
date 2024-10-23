<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Closure;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\SelectStatement;

/**
 * @template TRow of mixed
 * @extends QueryResult<SelectStatement, TRow>
 * @consistent-constructor
 */
class Paginator extends QueryResult
{
    /**
     * @param SelectStatement $statement
     * @param string $template
     * @param list<mixed> $parameters
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     * @param iterable<int, TRow> $rows
     * @param int $perPage
     * @param int $currentPage
     */
    public function __construct(
        SelectStatement $statement,
        string $template,
        array $parameters,
        float $elapsedMs,
        int|Closure $affectedRowCount,
        iterable $rows,
        public readonly int $perPage,
        public readonly int $currentPage,
    )
    {
        parent::__construct(
            $statement,
            $template,
            $parameters,
            $elapsedMs,
            $affectedRowCount,
            $rows,
        );
    }

    /**
     * @return bool
     */
    public function hasMorePages(): bool
    {
        return $this->count() !== $this->perPage;
    }

    /**
     * @return bool
     */
    public function isFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    /**
     * @return int
     */
    public function getNextPage(): ?int
    {
        return $this->hasMorePages() ? $this->currentPage + 1 : null;
    }

    public function getPreviousPage(): ?int
    {
        return $this->currentPage > 1 ? $this->currentPage - 1 : null;
    }

    /**
     * @return int
     */
    public function getStartingOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    /**
     * @return int
     */
    public function getEndingOffset(): int
    {
        return $this->getStartingOffset() + $this->count() - 1;
    }
}
