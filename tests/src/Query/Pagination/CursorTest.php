<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Pagination;

use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Query\Pagination\Cursor;

class CursorTest extends PaginatorTestCase
{
    public function test_init_asc(): void
    {
        $builder = $this->getCachedConnection()->query()->select()->from('t')->orderByAsc('id');
        $object = (object) ['id' => 1];
        $cursor = Cursor::init($builder, $object);
        $this->assertNotNull($cursor);
        $this->assertSame(['id' => 1], $cursor->parameters);
    }

    public function test_init_desc(): void
    {
        $builder = $this->getCachedConnection()->query()->select()->from('t')->orderByDesc('id');
        $object = (object) ['id' => 1];
        $cursor = Cursor::init($builder, $object);
        $this->assertNotNull($cursor);
        $this->assertSame(['id' => 1], $cursor->parameters);
    }

    public function test_init__without_ordering(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot paginate with cursor without an order by clause.');
        $builder = $this->getCachedConnection()->query()->select()->from('t');
        Cursor::init($builder, (object) ['id' => 1]);
    }

    public function test_applyTo(): void
    {
        $builder = $this->getCachedConnection()->query()->select()->from('t')
            ->orderByAsc('id')
            ->orderByDesc('name');
        $object = (object) ['id' => 1, 'name' => 'foo'];
        $cursor = Cursor::init($builder, $object);
        $this->assertInstanceOf(Cursor::class, $cursor);
        $this->assertInstanceOf(Cursor::class, $cursor->applyTo($builder));

        $this->assertSame(
            'SELECT * FROM "t" WHERE ("id", "name") > (1, \'foo\') ORDER BY "id", "name" DESC',
            $builder->toSql(),
        );
    }

    public function test_applyTo__without_ordering(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot paginate with cursor without an order by clause.');
        $builder = $this->getCachedConnection()->query()->select()->from('t')
            ->orderByAsc('id');
        $object = (object) ['id' => 1, 'name' => 'foo'];
        $cursor = Cursor::init($builder, $object);
        $cursor->applyTo($builder->reorder());
    }
}
