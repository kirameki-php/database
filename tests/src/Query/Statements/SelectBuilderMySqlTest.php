<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\LockOption;

final class SelectBuilderMySqlTest extends SelectBuilderTestAbstract
{
    protected string $useConnection = 'mysql';

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
}
