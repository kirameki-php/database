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
        $this->createRecords(12);
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->getNextCursorOrNull();
        $this->assertInstanceOf(Cursor::class, $nextCursor);
        $this->assertSame(10, $paginator->size);
        $this->assertSame(2, $nextCursor->page);
    }

    public function test_getNextCursor__no_next(): void
    {
        $this->createRecords(9);
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->getNextCursorOrNull();
        $this->assertNull($nextCursor);
        $this->assertSame(9, $paginator->count());
    }


    public function test_getNextCursor__empty(): void
    {
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->getNextCursorOrNull();
        $this->assertNull($nextCursor);
        $this->assertSame(0, $paginator->count());
    }
}
