<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\SelectStatement;
use Override;

/**
 * @template TRow of mixed
 * @extends Paginator<TRow>
 */
class CursorPaginator extends Paginator
{
    /**
     * @param QueryResult<SelectStatement, TRow> $result
     * @param Cursor $cursor,
     */
    public function __construct(
        QueryResult $result,
        protected Cursor $cursor,
    )
    {
        parent::__construct($result, $cursor->size, $cursor->page);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        return new static($this, $this->cursor);
    }

    /**
     * @return Cursor
     */
    public function getNextCursor(): Cursor
    {
        return Cursor::next($this, $this->cursor);
    }
}
