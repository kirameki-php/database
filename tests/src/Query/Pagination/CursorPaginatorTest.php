<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\Pagination\Cursor;
use Kirameki\Database\Query\Pagination\CursorPaginator;
use function dump;

class CursorPaginatorTest extends PaginatorTestCase
{
    /**
     * @param int $size
     * @param Cursor|null $cursor
     * @return CursorPaginator<object>
     */
    protected function createDummyPaginator(int $size, ?Cursor $cursor = null): CursorPaginator
    {
        return $this->getCachedConnection()->query()
            ->select()
            ->from('t')
            ->orderByAsc('id')
            ->cursorPaginate($size, $cursor);
    }

    public function test_properties(): void
    {
        $paginator = $this->createDummyPaginator(10);
        $this->assertInstanceOf(CursorPaginator::class, $paginator);
        $this->assertSame(10, $paginator->perPage);
        $this->assertInstanceOf(Cursor::class, $paginator->cursor);
    }

    public function test_instantiate(): void
    {
        $this->createRecords(12);
        $paginator = $this->createDummyPaginator(10);
        $newPaginator = $paginator->instantiate([]);
        $this->assertInstanceOf(CursorPaginator::class, $newPaginator);
        $this->assertSame(10, $newPaginator->perPage);
        $this->assertInstanceOf(Cursor::class, $paginator->cursor);
        $this->assertSame([], $newPaginator->all());
    }

    public function test_nextCursor__has_next(): void
    {
        $query = $this->getCachedConnection()->query()->select()->from('t')->orderByAsc('id');

        $this->createRecords(7);
        $paginator1 = $this->createDummyPaginator(3);
        $this->assertSame(3, $paginator1->perPage);
        $this->assertSame([0, 1, 2], $paginator1->map(fn($r) => ((array) $r)['id'])->all());

        $cursor2 = $paginator1->generateNextCursor();
        $this->assertInstanceOf(Cursor::class, $cursor2);
        $paginator2 = $query->cursorPaginate(3, $cursor2);
        $this->assertSame(3, $paginator2->perPage);
        $this->assertSame([3, 4, 5], $paginator2->map(fn($r) => ((array) $r)['id'])->all());

        $cursor3 = $paginator2->generateNextCursor();
        $this->assertInstanceOf(Cursor::class, $cursor3);
        $paginator3 = $query->cursorPaginate(3, $cursor3);
        $this->assertSame([6], $paginator3->map(fn($r) => ((array) $r)['id'])->all());
    }

    public function test_nextCursor__no_next(): void
    {
        $this->createRecords(9);
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->generateNextCursor();
        $this->assertNull($nextCursor);
        $this->assertSame(9, $paginator->count());
    }

    public function test_nextCursor__empty(): void
    {
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->generateNextCursor();
        $this->assertNull($nextCursor);
        $this->assertSame(0, $paginator->count());
    }
}
