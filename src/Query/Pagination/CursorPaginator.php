<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Kirameki\Core\Exceptions\LogicException;
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
     */
    public function __construct(
        QueryResult $result,
        int $size,
        int $page,
    )
    {
        parent::__construct($result, $size, $page);
    }

    /**
     * @return CursorConditions
     */
    protected function getNextConditions(): CursorConditions
    {
        $orderBy = $this->statement->orderBy ?? [];

        if ($orderBy === []) {
            throw new LogicException('Cannot paginate with cursor without an order by clause.');
        }

        $last = $this->last();

        $conditions = new CursorConditions($this->page + 1);
        foreach ($orderBy as $column => $order) {
            $conditions->add($column, $order, $last[$column]);
        }
        return $conditions;
    }
}
