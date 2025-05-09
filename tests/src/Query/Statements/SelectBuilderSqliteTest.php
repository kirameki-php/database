<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\LockOption;
use function dump;

final class SelectBuilderSqliteTest extends SelectBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_forceIndex(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id')->forceIndex('force')->toSql();
        $this->assertSame('SELECT "id" FROM "User" INDEXED BY "force"', $sql);
    }

    public function test_lockForUpdate(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate()->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $sql);
    }

    public function test_lockForUpdate_with_option_nowait(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::Nowait)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $sql);
    }

    public function test_lockForUpdate_with_option_skip_locked(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('SQLite does not support SKIP LOCKED');
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::SkipLocked)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $sql);
    }

    public function test_lockForShare(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forShare()->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $sql);
    }

    public function test_explain(): void
    {
        $conn = $this->createTempConnection($this->useConnection);
        $table = $conn->schema()->createTable('t');
        $table->id();
        $table->execute();
        $conn->query()->insertInto('t')->value(['id' => 1])->execute();
        $result = $conn->query()->select()->from('t')->where('id', 1)->explain();
        $explain = (array) $result->first();
        $this->assertSame(0, $explain['addr']);
        $this->assertSame('Init', $explain['opcode']);
    }
}
