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
        get => (int) ceil($this->totalRows / $this->size);
    }

    /**
     * @param QueryResult<SelectStatement, TRow> $result
     * @param int $size
     * @param int $page
     * @param int $totalRows
     */
    public function __construct(
        QueryResult $result,
        int $size,
        int $page,
        public readonly int $totalRows,
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
        $instantiate = new static($this, $this->size, $this->page, $this->totalRows);
        $instantiate->items = $iterable;
        return $instantiate;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function hasMorePages(): bool
    {
        return $this->page < $this->totalPages;
    }

    /**
     * @return bool
     */
    public function isLastPage(): bool
    {
        return $this->page === $this->totalPages;
    }
}
