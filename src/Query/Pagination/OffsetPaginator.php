<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Pagination;

use Closure;
use Kirameki\Database\Query\Statements\SelectStatement;
use Override;
use function ceil;

/**
 * @template TRow of mixed
 * @extends Paginator<TRow>
 * @consistent-constructor
 */
class OffsetPaginator extends Paginator
{
    /**
     * @param SelectStatement $statement
     * @param string $template
     * @param list<mixed> $parameters
     * @param float $elapsedMs
     * @param Closure(): int $affectedRowCount
     * @param iterable<int, TRow> $rows
     * @param int $perPage
     * @param int $currentPage
     */
    public function __construct(
        SelectStatement $statement,
        string $template,
        array $parameters,
        float $elapsedMs,
        int|Closure $affectedRowCount,
        iterable $rows,
        int $perPage,
        int $currentPage,
        public readonly int $totalCount,
    )
    {
        parent::__construct(
            $statement,
            $template,
            $parameters,
            $elapsedMs,
            $affectedRowCount,
            $rows,
            $perPage,
            $currentPage,
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }

    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return (int) ceil($this->totalCount / $this->perPage);
    }

    /**
     * @return bool
     */
    public function isLastPage(): bool
    {
        return $this->currentPage === $this->getTotalPages();
    }
}
