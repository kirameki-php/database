<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\SelectStatement;
use Override;
use function ceil;

/**
 * @template TRow of mixed
 * @extends Paginator<TRow>
 */
class OffsetPaginator extends Paginator
{
    /**
     * @var int
     */
    public int $totalPages {
        get => (int) ceil($this->totalRows / $this->pageSize);
    }

    /**
     * @param QueryResult<SelectStatement, TRow> $result
     * @param int $pageSize
     * @param int $page
     * @param int $totalRows
     */
    public function __construct(
        QueryResult $result,
        int $pageSize,
        public readonly int $page,
        public readonly int $totalRows,
    )
    {
        parent::__construct($result, $pageSize);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        $instantiate = new static($this, $this->pageSize, $this->page, $this->totalRows);
        $instantiate->items = $iterable;
        return $instantiate;
    }

    /**
     * @return bool
     */
    protected function hasMorePages(): bool
    {
        return $this->page < $this->totalPages;
    }

    /**
     * @return bool
     */
    public function isFirstPage(): bool
    {
        return $this->page === 1;
    }

    /**
     * @return bool
     */
    public function isLastPage(): bool
    {
        return $this->page === $this->totalPages;
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
        return ($this->page - 1) * $this->pageSize + 1;
    }

    /**
     * @return int
     */
    public function getEndingOffset(): int
    {
        return $this->getStartingOffset() + $this->count() - 1;
    }
}
