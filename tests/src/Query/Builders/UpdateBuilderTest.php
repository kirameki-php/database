<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Builders;

use Tests\Kirameki\Database\Query\QueryTestCase;

class UpdateBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function test_update_value(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1])->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1", $sql);
    }

    public function test_update_values(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1, 'name' => 'abc'])->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1, `name` = 'abc'", $sql);
    }

    public function test_update_with_where(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1])->where('lock', 1)->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1 WHERE `lock` = 1", $sql);
    }

    public function test_update_with_condition(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1])->where('lock', 1)->orderByDesc('id')->limit(1)->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1 WHERE `lock` = 1 ORDER BY `id` DESC LIMIT 1", $sql);
    }

    public function test_returning(): void
    {
        $sql = $this->updateBuilder()->table('User')->set(['status'=> 1])->returning('id', 'status')->toSql();
        static::assertEquals("UPDATE `User` SET `status` = 1 RETURNING `id`, `status`", $sql);
    }
}
