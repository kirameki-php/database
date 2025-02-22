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
        $this->assertNull($paginator->currentCursor);
    }

    public function test_instantiate(): void
    {
        $this->createRecords(12);
        $paginator = $this->createDummyPaginator(10);
        $newPaginator = $paginator->instantiate([]);
        $this->assertInstanceOf(CursorPaginator::class, $newPaginator);
        $this->assertSame(10, $newPaginator->size);
        $this->assertSame(1, $newPaginator->page);
        $this->assertInstanceOf(Cursor::class, $paginator->currentCursor);
        $this->assertSame([], $newPaginator->all());
    }

    public function test_nextCursor__has_next(): void
    {
        $query = $this->getCachedConnection()->query()->select()->from('t')->orderByAsc('id');

        $this->createRecords(7);
        $paginator1 = $this->createDummyPaginator(3);
        $this->assertSame(3, $paginator1->size);
        $this->assertSame(1, $paginator1->page);
        $this->assertSame([0, 1, 2], $paginator1->map(fn($r) => ((array) $r)['id'])->all());

        $cursor2 = $paginator1->nextCursor;
        $this->assertInstanceOf(Cursor::class, $cursor2);
        $this->assertSame(2, $cursor2->page);
        $paginator2 = $query->cursorPaginate(3, $cursor2);
        $this->assertSame(3, $paginator2->size);
        $this->assertSame(2, $paginator2->page);
        $this->assertSame([3, 4, 5], $paginator2->map(fn($r) => ((array) $r)['id'])->all());

        $cursor3 = $paginator2->nextCursor;
        $this->assertInstanceOf(Cursor::class, $cursor3);
        $this->assertSame(3, $cursor3->page);
        $paginator3 = $query->cursorPaginate(3, $cursor3);
        $this->assertSame([6], $paginator3->map(fn($r) => ((array) $r)['id'])->all());
    }

    public function test_nextCursor__no_next(): void
    {
        $this->createRecords(9);
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->nextCursor;
        $this->assertNull($nextCursor);
        $this->assertSame(9, $paginator->count());
        $this->assertFalse($paginator->hasNextPage());
    }

    public function test_nextCursor__empty(): void
    {
        $paginator = $this->createDummyPaginator(10);
        $nextCursor = $paginator->nextCursor;
        $this->assertNull($nextCursor);
        $this->assertSame(0, $paginator->count());
    }

    public function test_previousCursor__has_prev(): void
    {
        $query = $this->getCachedConnection()->query()->select()->from('t')->orderByAsc('id');

        $this->createRecords(7);
        $paginator1 = $this->createDummyPaginator(3);
        $this->assertSame([0, 1, 2], $paginator1->map(fn($r) => ((array) $r)['id'])->all());

        $cursor2 = $paginator1->nextCursor;
        $paginator2 = $query->cursorPaginate(3, $cursor2);
        $this->assertSame([3, 4, 5], $paginator2->map(fn($r) => ((array) $r)['id'])->all());

        $cursor3 = $paginator2->nextCursor;
        $paginator3 = $query->cursorPaginate(3, $cursor3);
        $this->assertSame([6], $paginator3->map(fn($r) => ((array) $r)['id'])->all());

        $cursorRev1 = $paginator3->previousCursor;
        $paginatorRev1 = $query->cursorPaginate(3, $cursorRev1);
        $this->assertInstanceOf(Cursor::class, $cursorRev1);
        $this->assertSame(2, $cursorRev1->page);
        $this->assertSame([3, 4, 5], $paginatorRev1->map(fn($r) => ((array) $r)['id'])->all());

        $cursorRev2 = $paginatorRev1->previousCursor;
        $paginatorRev2 = $query->cursorPaginate(3, $cursorRev2);
        $this->assertInstanceOf(Cursor::class, $cursorRev2);
        $this->assertSame(1, $cursorRev2->page);
        $this->assertSame([0, 1, 2], $paginatorRev2->map(fn($r) => ((array) $r)['id'])->all());
        $this->assertNull($paginatorRev2->previousCursor);
    }

    public function test_previousCursor__empty(): void
    {
        $paginator = $this->createDummyPaginator(10);
        $previousCursor = $paginator->previousCursor;
        $this->assertNull($previousCursor);
        $this->assertSame(0, $paginator->count());
    }
}
