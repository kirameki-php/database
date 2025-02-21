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
     * @param TRow|null $next
     * @param Cursor $cursor
     * @param int $size
     */
    public function __construct(
        QueryResult $result,
        public readonly ?object $next,
        public readonly ?Cursor $cursor,
        int $size,
    )
    {
        parent::__construct($result, $size, $cursor->page ?? 1);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        $instance = new static($this, $this->next, $this->cursor, $this->size);
        $instance->items = $iterable;
        return $instance;
    }

    /**
     * @return Cursor|null
     */
    public function getNextCursorOrNull(): ?Cursor
    {
        return $this->cursor?->next($this->next);
    }
}
