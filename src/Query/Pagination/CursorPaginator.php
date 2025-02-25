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
     * @var Cursor|null
     */
    public ?Cursor $nextCursor {
        get => $this->nextCursor ??= $this->nextRow !== null
            ? $this->currentCursor?->toNext($this->nextRow)
            : null;
    }

    /**
     * @return Cursor|null
     */
    public ?Cursor $previousCursor {
        get => $this->previousCursor ??= $this->page > 1
            ? $this->currentCursor?->toPrevious($this->first())
            : null;
    }

    /**
     * @param QueryResult<SelectStatement, TRow> $result
     * @param int $pageSize
     * @param TRow|null $nextRow
     * @param ($nextRow is null ? null : Cursor) $currentCursor
     */
    public function __construct(
        QueryResult $result,
        int $pageSize,
        protected readonly ?object $nextRow,
        public readonly ?Cursor $currentCursor,
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
        $instance = new static($this, $this->pageSize, $this->currentCursor);
        $instance->items = $iterable;
        return $instance;
    }
}
