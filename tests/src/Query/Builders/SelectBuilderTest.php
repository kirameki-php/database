<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Builders\JoinBuilder;
use Kirameki\Database\Query\Expressions\Raw;
use Kirameki\Database\Query\Support\LockOption;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Query\Builders\ConditionBuilder;
use Tests\Kirameki\Database\Query\QueryTestCase;

class SelectBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function test_plain(): void
    {
        $sql = $this->selectBuilder()->columns(new Raw('1'))->toSql();
        static::assertEquals("SELECT 1", $sql);
    }

    public function test_from(): void
    {
        $sql = $this->selectBuilder()->from('User')->toSql();
        static::assertEquals("SELECT * FROM `User`", $sql);
    }

    public function test_from_with_alias(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u`", $sql);
    }

    public function test_from_multiple(): void
    {
        $sql = $this->selectBuilder()->from('User AS u', 'UserItem')->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u`, `UserItem`", $sql);
    }

    public function test_from_multiple_where_column(): void
    {
        $sql = $this->selectBuilder()
            ->columns('User.*')
            ->from('User', 'UserItem')
            ->whereColumn('User.id', 'UserItem.userId')
            ->toSql();
        static::assertEquals("SELECT `User`.* FROM `User`, `UserItem` WHERE `User`.`id` = `UserItem`.`userId`", $sql);
    }

    public function test_columns(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id', 'name')->toSql();
        static::assertEquals("SELECT `id`, `name` FROM `User`", $sql);
    }

    public function test_columns_with_alias(): void
    {
        $sql = $this->selectBuilder()->from('User as u')->columns('u.*', 'u.name')->toSql();
        static::assertEquals("SELECT `u`.*, `u`.`name` FROM `User` AS `u`", $sql);
    }

    public function test_distinct(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id')->distinct()->toSql();
        static::assertEquals("SELECT DISTINCT `id` FROM `User`", $sql);
    }

    public function test_join_using_on(): void
    {
        $sql = $this->selectBuilder()->from('User')->join('Device', fn(JoinBuilder $join) => $join->on('User.id', 'Device.userId'))->toSql();
        static::assertEquals("SELECT * FROM `User` JOIN `Device` ON `User`.`id` = `Device`.`userId`", $sql);
    }

    public function test_join_using_on_and_where(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')
            ->join('Device AS d', fn(JoinBuilder $join) => $join->on('u.id', 'd.userId')->where('id', [1,2]))
            ->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u` JOIN `Device` AS `d` ON `u`.`id` = `d`.`userId` AND `id` IN (?, ?)", $sql);
    }

    public function test_joinOn(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->joinOn('Device AS d', 'u.id', 'd.userId')->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u` JOIN `Device` AS `d` ON `u`.`id` = `d`.`userId`", $sql);
    }

    public function test_lockForUpdate(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate()->toSql();
        static::assertEquals("SELECT * FOR UPDATE FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_lockForUpdate_with_option_nowait(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::Nowait)->toSql();
        static::assertEquals("SELECT * FOR UPDATE NOWAIT FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_lockForUpdate_with_option_skip_locked(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::SkipLocked)->toSql();
        static::assertEquals("SELECT * FOR UPDATE SKIP LOCKED FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_lockForShare(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forShare()->toSql();
        static::assertEquals("SELECT * FOR SHARE FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_where_with_two_args(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', fn() => 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', [3, 4])->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` IN (3, 4)", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', Range::closed(1, 2))->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` >= 1 AND `id` <= 2", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_where_with_three_args(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', '=', 1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_where_multiples(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->where('status', 0)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 AND `status` = 0", $sql);
    }

    public function test_where_combined(): void
    {
        $sql = $this->selectBuilder()->from('User')
            ->where(ConditionBuilder::for('id')->lessThan(1)->or()->equals(3))
            ->whereNot('id', -1)
            ->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE (`id` < 1 OR `id` = 3) AND `id` != -1", $sql);
    }

    public function test_where_column(): void
    {
        $sql = $this->selectBuilder()->from('User')->whereColumn('User.id', 'Device.userId')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `User`.`id` = `Device`.`userId`", $sql);
    }

    public function test_where_column_aliased(): void
    {
        $sql = $this->selectBuilder()->from('User AS u', 'Device AS d')->whereColumn('u.id', 'd.userId')->toSql();
        static::assertEquals("SELECT * FROM `User` AS `u`, `Device` AS `d` WHERE `u`.`id` = `d`.`userId`", $sql);
    }

    public function test_orderBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderBy('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` ASC", $sql);
    }

    public function test_orderByDesc(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` DESC", $sql);
    }

    public function test_groupBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->groupBy('status')->toSql();
        static::assertEquals("SELECT * FROM `User` GROUP BY `status`", $sql);
    }

    public function test_reorder(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->reorder()->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_where_and_limit(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1", $sql);
    }

    public function test_where_and_offset(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->offset(10)->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1 OFFSET 10", $sql);
    }

    public function test_combination(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->groupBy('status')->having('status', 1)->limit(2)->orderBy('id')->toSql();
        static::assertEquals("SELECT * FROM `User` WHERE `id` = 1 GROUP BY `status` ORDER BY `id` ASC LIMIT 2", $sql);
    }

    public function test_clone(): void
    {
        $where = ConditionBuilder::for('id')->equals(1)->or('id')->equals(2);
        $base = $this->selectBuilder()->from('User')->where($where);
        $copy = clone $base;
        $where->or()->in([3,4]); // change $base but should not be reflected on copy
        static::assertEquals("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2 OR `id` IN (3, 4))", $base->toSql());
        static::assertEquals("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2)", $copy->toSql());
        static::assertNotEquals($base->toSql(), $copy->toSql());
    }
}
