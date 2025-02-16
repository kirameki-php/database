<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\LockOption;
use Tests\Kirameki\Database\Query\QueryTestCase;

class SqliteQuerySyntaxTest extends QueryTestCase
{
    public function test_use_index(): void
    {
        $query = $this->sqliteConnection()->query()->select()->from('a')->forceIndex('idx');
        $this->assertSame('SELECT * FROM "a" INDEXED BY "idx"', $query->toString());
    }

    public function test_lock_nowait(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Sqlite does not support NOWAIT or SKIP LOCKED!');
        $this->sqliteConnection()->query()->select()->from('a')->forUpdate(LockOption::Nowait)->toString();
    }

    public function test_lock_skip_locked(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Sqlite does not support NOWAIT or SKIP LOCKED!');
        $this->sqliteConnection()->query()->select()->from('a')->forUpdate(LockOption::SkipLocked)->toString();
    }
}
