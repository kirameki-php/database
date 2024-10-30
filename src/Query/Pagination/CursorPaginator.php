<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\SelectStatement;

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
     * @return Cursor
     */
    protected function getNextCursor(): Cursor
    {
        return Cursor::next($this, $this->cursor);
    }
}
