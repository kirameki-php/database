<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Tests\Kirameki\Database\Query\QueryTestCase;

class UpdateBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function test_update_value(): void
    {
        $sql = $this->updateBuilder('User')->set(['status'=> 1])->toString();
        $this->assertSame("UPDATE `User` SET `status` = 1", $sql);
    }

    public function test_update_values(): void
    {
        $sql = $this->updateBuilder('User')->set(['status'=> 1, 'name' => 'abc'])->toString();
        $this->assertSame("UPDATE `User` SET `status` = 1, `name` = \"abc\"", $sql);
    }

    public function test_update_with_where(): void
    {
        $sql = $this->updateBuilder('User')->set(['status'=> 1])->where('lock', 1)->toString();
        $this->assertSame("UPDATE `User` SET `status` = 1 WHERE `lock` = 1", $sql);
    }

    public function test_returning(): void
    {
        $sql = $this->updateBuilder('User')->set(['status'=> 1])->returning('id', 'status')->toString();
        $this->assertSame("UPDATE `User` SET `status` = 1 RETURNING `id`, `status`", $sql);
    }
}
