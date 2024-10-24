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
     * @param int $size
     * @param int $page
     * @param string $cursor
     */
    public function __construct(
        QueryResult $result,
        int $size,
        int $page,
        public readonly string $cursor,
    )
    {
        parent::__construct($result, $size, $page);
    }
}
