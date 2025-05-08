<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class DeleteBuilderTestAbstract extends QueryTestCase
{

    abstract public function test_delete__all_with_drop_protection_enabled(): void;

    abstract public function test_delete__all_with_drop_protection_disabled(): void;

    public function test_delete_all(): void
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
