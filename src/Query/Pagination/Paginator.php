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
    public const int DEFAULT_PER_PAGE = 30;

    /**
     * @param QueryResult<SelectStatement, mixed> $result
     * @param int $perPage
     */
    public function __construct(
        QueryResult $result,
        public readonly int $perPage,
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
}
