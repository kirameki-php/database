<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Builders;

use Tests\Kirameki\Database\Query\QueryTestCase;

class DeleteBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function test_deleteAll(): void
    {
        $sql = $this->deleteBuilder('User')->toString();
        $this->assertSame("DELETE FROM `User`", $sql);
    }

    public function test_delete_with_where(): void
    {
        $sql = $this->deleteBuilder('User')->where('id', 1)->toString();
        $this->assertSame("DELETE FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_returning(): void
    {
        $sql = $this->deleteBuilder('User')->where('id', 1)->returning('id', 'name')->toString();
        $this->assertSame("DELETE FROM `User` WHERE `id` = 1 RETURNING `id`, `name`", $sql);
    }
}
