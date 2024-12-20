<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\Select\SelectStatement;
use Override;
use function ceil;

/**
 * @template TRow of mixed
 * @extends Paginator<TRow>
 */
class OffsetPaginator extends Paginator
{
    /**
     * @param QueryResult<SelectStatement, TRow> $result
     * @param int $size
     * @param int $page
     * @param int $total
     */
    public function __construct(
        QueryResult $result,
        int $size,
        int $page,
        public readonly int $total,
    )
    {
        parent::__construct($result, $size, $page);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        return new static($this, $this->size, $this->page, $this->total);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function hasMorePages(): bool
    {
        return $this->page < $this->getTotalPages();
    }

    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return (int) ceil($this->total / $this->size);
    }

    /**
     * @return bool
     */
    public function isLastPage(): bool
    {
        return $this->page === $this->getTotalPages();
    }
}
