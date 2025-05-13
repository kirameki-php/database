<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\LockOption;
use Kirameki\Database\Query\Statements\NullOrder;

final class SelectBuilderMySqlTest extends SelectBuilderTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_forceIndex(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id')->forceIndex('force')->toSql();
        $this->assertSame('SELECT "id" FROM "User" FORCE INDEX ("force")', $sql);
    }

    public function test_lockForUpdate(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate()->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 FOR UPDATE', $sql);
    }

    public function test_lockForUpdate_with_option_nowait(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::Nowait)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 FOR UPDATE NOWAIT', $sql);
    }

    public function test_lockForUpdate_with_option_skip_locked(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::SkipLocked)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 FOR UPDATE SKIP LOCKED', $sql);
    }

    public function test_lockForShare(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forShare()->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 FOR SHARE', $sql);
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
        $this->assertSame(1, $explain['id']);
        $this->assertSame('SIMPLE', $explain['select_type']);
        $this->assertSame('t', $explain['table']);
        $this->assertSame('PRIMARY', $explain['key']);
    }

    public function test_orderBy_with_nulls_last(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByAsc('id', NullOrder::Last)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 ORDER BY "id" IS NULL, "id"', $sql);
    }
}
