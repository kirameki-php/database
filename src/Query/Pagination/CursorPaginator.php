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
        get => $this->nextCursor ??= $this->currentCursor?->nextOrNull($this->nextRow);
    }

    /**
     * @return Cursor|null
     */
    public ?Cursor $previousCursor {
        get => $this->previousCursor ??= $this->currentCursor?->previousOrNull($this->firstOrNull());
    }

    /**
     * @param QueryResult<SelectStatement, TRow> $result
     * @param TRow|null $nextRow
     * @param ($nextRow is null ? null : Cursor) $currentCursor
     * @param int $size
     */
    public function __construct(
        QueryResult $result,
        protected readonly ?object $nextRow,
        public readonly ?Cursor $currentCursor,
        int $size,
    )
    {
        parent::__construct($result, $size, $currentCursor->page ?? 1);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        $instance = new static($this, $this->nextRow, $this->currentCursor, $this->size);
        $instance->items = $iterable;
        return $instance;
    }
}
