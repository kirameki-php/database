<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

class DeleteBuilderSqliteTest extends DeleteBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_deleteAll(): void
    {
        $sql = $this->deleteBuilder('User')->toSql();
        $this->assertSame('DELETE FROM "User"', $sql);
    }

    public function test_delete_with_where(): void
    {
        $sql = $this->deleteBuilder('User')->where('id', 1)->toSql();
        $this->assertSame('DELETE FROM "User" WHERE "id" = 1', $sql);
    }

    public function test_returning(): void
    {
        $sql = $this->deleteBuilder('User')->where('id', 1)->returning('id', 'name')->toSql();
        $this->assertSame('DELETE FROM "User" WHERE "id" = 1 RETURNING "id", "name"', $sql);
    }
}
