<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\SelectStatement;

/**
 * @template TRow of mixed
 * @extends QueryResult<SelectStatement, TRow>
 */
abstract class Paginator extends QueryResult
{
    public const int DEFAULT_SIZE = 30;

    /**
     * @param QueryResult<SelectStatement, mixed> $result
     * @param int $size
     * @param int $page
     */
    public function __construct(
        QueryResult $result,
        public readonly int $size,
        public readonly int $page,
    )
    {
        parent::__construct(
            $result->statement,
            $result->template,
            $result->parameters,
            $result->elapsedMs,
            $result->affectedRowCount,
            $result->items,
        );
    }

    /**
     * @return bool
     */
    protected function hasMorePages(): bool
    {
        return $this->count() === $this->size;
    }

    /**
     * @return bool
     */
    public function isFirstPage(): bool
    {
        return $this->page === 1;
    }

    /**
     * @return int|null
     */
    public function getNextPage(): ?int
    {
        return $this->hasMorePages() ? $this->page + 1 : null;
    }

    /**
     * @return int|null
     */
    public function getPreviousPage(): ?int
    {
        return $this->page > 1 ? $this->page - 1 : null;
    }

    /**
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->getNextPage() !== null;
    }

    /**
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->getPreviousPage() !== null;
    }

    /**
     * @return int
     */
    public function getStartingOffset(): int
    {
        return ($this->page - 1) * $this->size + 1;
    }

    /**
     * @return int
     */
    public function getEndingOffset(): int
    {
        return $this->getStartingOffset() + $this->count() - 1;
    }
}
