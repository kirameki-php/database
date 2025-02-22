<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\Pagination\Cursor;
use Kirameki\Database\Query\Pagination\CursorPaginator;
use stdClass;
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
        $this->assertSame(10, $paginator->size);
        $this->assertSame(1, $paginator->page);
        $this->assertNull($paginator->cursor);
    }

    public function test_instantiate(): void
    {
        $this->createRecords(12);
        $paginator = $this->createDummyPaginator(10);
        $newPaginator = $paginator->instantiate([]);
        $this->assertInstanceOf(CursorPaginator::class, $newPaginator);
        $this->assertSame(10, $newPaginator->size);
        $this->assertSame(1, $newPaginator->page);
        $this->assertInstanceOf(Cursor::class, $paginator->cursor);
        $this->assertSame([], $newPaginator->all());
    }

    public function test_getNextCursor__has_next(): void
    {
        $this->createRecords(7);
        $paginator = $this->createDummyPaginator(3);
        $nextCursor = $paginator->getNextCursorOrNull();
        $this->assertInstanceOf(Cursor::class, $nextCursor);
        $this->assertSame(3, $paginator->size);
        $this->assertSame(2, $nextCursor->page);
        $this->assertSame([0, 1, 2], $paginator->map(fn($r) => ((array) $r)['id'])->all());

        $nextPaginator = $this->getCachedConnection()->query()->select()->from('t')
            ->orderByAsc('id')
            ->cursorPaginate(3, $nextCursor);
        $this->assertInstanceOf(CursorPaginator::class, $nextPaginator);
        $this->assertSame(3, $nextPaginator->size);
        $this->assertSame(2, $nextPaginator->page);
        $this->assertSame([3, 4, 5], $nextPaginator->map(fn($r) => ((array) $r)['id'])->all());
    }

    public function test_getNextCursor__no_next(): void
    {
        $this->createRecords(9);
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->getNextCursorOrNull();
        $this->assertNull($nextCursor);
        $this->assertSame(9, $paginator->count());
        $this->assertFalse($paginator->hasNextPage());
    }


    public function test_getNextCursor__empty(): void
    {
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->getNextCursorOrNull();
        $this->assertNull($nextCursor);
        $this->assertSame(0, $paginator->count());
    }
}
