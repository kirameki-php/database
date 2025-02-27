<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\Pagination\OffsetPaginator;
use function dump;

class OffsetPaginatorTest extends PaginatorTestCase
{
    /**
     * @return OffsetPaginator<mixed>
     */
    protected function createDummyPaginator(int $size, int $page): OffsetPaginator
    {
        return $this->getCachedConnection()->query()
            ->select()
            ->from('t')
            ->offsetPaginate($page, $size);
    }

    public function test_properties(): void
    {
        $this->createRecords(11);
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertInstanceOf(OffsetPaginator::class, $paginator);
        $this->assertSame(10, $paginator->perPage);
        $this->assertSame(1, $paginator->page);
        $this->assertSame(11, $paginator->totalRows);
    }

    public function test_instantiate(): void
    {
        $this->createRecords(11);
        $paginator = $this->createDummyPaginator(10, 1);
        $newPaginator = $paginator->instantiate([]);
        $this->assertInstanceOf(OffsetPaginator::class, $newPaginator);
        $this->assertSame(10, $newPaginator->perPage);
        $this->assertSame(1, $newPaginator->page);
        $this->assertSame(11, $newPaginator->totalRows);
        $this->assertSame([], $newPaginator->all());
    }

    public function test_isFirstPage__true(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertTrue($paginator->isFirstPage());
    }

    public function test_isFirstPage__false(): void
    {
        $paginator = $this->createDummyPaginator(10, 2);
        $this->assertFalse($paginator->isFirstPage());
    }

    public function test_isLastPage__false(): void
    {
        $this->createRecords(11);
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertFalse($paginator->isLastPage());
    }

    public function test_isLastPage__true(): void
    {
        $this->createRecords(11);
        $paginator = $this->createDummyPaginator(10, 2);
        $this->assertTrue($paginator->isLastPage());
    }

    public function test_getNextPage__no_record(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertNull($paginator->getNextPage());
    }

    public function test_getNextPage__less_than_size(): void
    {
        $this->createRecords(1);
        $paginator = $this->createDummyPaginator(2, 1);
        $this->assertNull($paginator->getNextPage());
    }

    public function test_getNextPage__exact_size(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(2, 1);
        $this->assertNull($paginator->getNextPage());
    }

    public function test_getNextPage__greater_than_size(): void
    {
        $this->createRecords(3);
        $paginator = $this->createDummyPaginator(2, 1);
        $this->assertSame(2, $paginator->getNextPage());
    }

    public function test_getNextPage__greater_than_size_next_page(): void
    {
        $this->createRecords(3);
        $paginator = $this->createDummyPaginator(2, 2);
        $this->assertNull($paginator->getNextPage());
    }

    public function test_getTotalPages(): void
    {
        $this->createRecords(11);
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertSame(2, $paginator->totalPages);
    }

    public function test_getNextPage__returns_null(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertNull($paginator->getNextPage());
    }

    public function test_getNextPage__returns_page(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(1, 1);
        $this->assertSame(2, $paginator->getNextPage());
    }

    public function test_getPreviousPage__returns_null(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertNull($paginator->getPreviousPage());
    }

    public function test_getPreviousPage__returns_page(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(2, 2);
        $this->assertSame(1, $paginator->getPreviousPage());
    }

    public function test_hasNextPage__false(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertFalse($paginator->hasNextPage());
    }

    public function test_hasNextPage__true(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(1, 1);
        $this->assertTrue($paginator->hasNextPage());
    }

    public function test_hasPreviousPage__false(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertFalse($paginator->hasPreviousPage());
    }

    public function test_hasPreviousPage__true(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(2, 2);
        $this->assertTrue($paginator->hasPreviousPage());
    }

    public function test_getStartingOffset_first_page(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertSame(1, $paginator->getStartingOffset());
    }

    public function test_getStartingOffset__page_2(): void
    {
        $paginator = $this->createDummyPaginator(10, 2);
        $this->assertSame(11, $paginator->getStartingOffset());
    }

    public function test_getEndingOffset_first_page(): void
    {
        $this->createRecords(10);
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertSame(10, $paginator->getEndingOffset());
    }

    public function test_getEndingOffset__page_2(): void
    {
        $this->createRecords(19);
        $paginator = $this->createDummyPaginator(10, 2);
        $this->assertSame(19, $paginator->getEndingOffset());
    }
}
