<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\Pagination\Paginator;

class PaginatorTest extends PaginatorTestCase
{
    /**
     * @return Paginator<mixed>
     */
    protected function createDummyPaginator(int $size, int $page): Paginator
    {
        $result = $this->getCachedConnection()->query()
            ->select()
            ->from('t')
            ->offsetPaginate($page, $size);
        return new class($result, $size, $page) extends Paginator {};
    }

    public function test_properties(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertSame(10, $paginator->size);
        $this->assertSame(1, $paginator->page);
    }

    public function test_hasMorePages__false(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertFalse($paginator->hasMorePages());
    }

    public function test_hasMorePages__true(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(2, 1);
        $this->assertTrue($paginator->hasMorePages());
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

    public function test_getNextPage__returns_null(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertNull($paginator->getNextPage());
    }

    public function test_getNextPage__returns_page(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(2, 1);
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
        $paginator = $this->createDummyPaginator(2, 1);
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
