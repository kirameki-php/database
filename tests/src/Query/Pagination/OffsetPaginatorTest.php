<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Pagination;

use Kirameki\Database\Query\Pagination\OffsetPaginator;

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
        $this->assertSame(10, $paginator->size);
        $this->assertSame(1, $paginator->page);
        $this->assertSame(11, $paginator->total);
    }

    public function test_instantiate(): void
    {
        $this->createRecords(11);
        $paginator = $this->createDummyPaginator(10, 1);
        $newPaginator = $paginator->instantiate([]);
        $this->assertInstanceOf(OffsetPaginator::class, $newPaginator);
        $this->assertSame(10, $newPaginator->size);
        $this->assertSame(1, $newPaginator->page);
        $this->assertSame(11, $newPaginator->total);
        $this->assertSame([], $newPaginator->all());
    }

    public function test_hasMorePages__false(): void
    {
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertFalse($paginator->hasMorePages());
    }

    public function test_hasMorePages__true(): void
    {
        $this->createRecords(2);
        $paginator = $this->createDummyPaginator(1, 1);
        $this->assertTrue($paginator->hasMorePages());
    }

    public function test_getTotalPages(): void
    {
        $this->createRecords(11);
        $paginator = $this->createDummyPaginator(10, 1);
        $this->assertSame(2, $paginator->getTotalPages());
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
}
