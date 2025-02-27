<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\SelectStatement;
use Override;

/**
 * @template TRow of object
 * @extends Paginator<TRow>
 */
class CursorPaginator extends Paginator
{
    /**
     * @param QueryResult<SelectStatement, TRow> $result
     * @param int $perPage
     * @param Cursor $cursor
     * @param bool $hasNext
     */
    public function __construct(
        QueryResult $result,
        int $perPage,
        public readonly Cursor $cursor,
        protected readonly bool $hasNext,
    )
    {
        parent::__construct($result, $perPage);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        $instance = new static($this, $this->perPage, $this->cursor, $this->hasNext);
        $instance->items = $iterable;
        return $instance;
    }

    /**
     * @return Cursor|null
     */
    public function generateNextCursor(): ?Cursor
    {
        return $this->hasNext
            ? $this->cursor->toNext($this->last())
            : null;
    }
}
