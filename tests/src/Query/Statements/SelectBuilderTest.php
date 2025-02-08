<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\JoinBuilder;
use Kirameki\Database\Query\Statements\LockOption;
use Kirameki\Database\Raw;
use Kirameki\Time\Time;
use Tests\Kirameki\Database\Query\Statements\_Support\IntCastEnum;
use Tests\Kirameki\Database\Query\QueryTestCase;

class SelectBuilderTest extends QueryTestCase
{
    protected string $connection = 'mysql';

    public function test_plain(): void
    {
        $sql = $this->selectBuilder()->columns(new Raw('1'))->toString();
        $this->assertSame("SELECT 1", $sql);
    }

    public function test_from(): void
    {
        $sql = $this->selectBuilder()->from('User')->toString();
        $this->assertSame("SELECT * FROM `User`", $sql);
    }

    public function test_from_with_alias(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->toString();
        $this->assertSame("SELECT * FROM `User` AS `u`", $sql);
    }

    public function test_from_multiple(): void
    {
        $sql = $this->selectBuilder()->from('User AS u', 'UserItem')->toString();
        $this->assertSame("SELECT * FROM `User` AS `u`, `UserItem`", $sql);
    }

    public function test_from_multiple_where_column(): void
    {
        $sql = $this->selectBuilder()
            ->columns('User.*')
            ->from('User', 'UserItem')
            ->whereColumn('User.id', 'UserItem.userId')
            ->toString();
        $this->assertSame("SELECT `User`.* FROM `User`, `UserItem` WHERE `User`.`id` = `UserItem`.`userId`", $sql);
    }

    public function test_columns(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id', 'name')->toString();
        $this->assertSame("SELECT `id`, `name` FROM `User`", $sql);
    }

    public function test_columns_with_alias(): void
    {
        $sql = $this->selectBuilder()->from('User as u')->columns('u.*', 'u.name')->toString();
        $this->assertSame("SELECT `u`.*, `u`.`name` FROM `User` AS `u`", $sql);
    }

    public function test_distinct(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id')->distinct()->toString();
        $this->assertSame("SELECT DISTINCT `id` FROM `User`", $sql);
    }

    public function test_join_using_on(): void
    {
        $sql = $this->selectBuilder()->from('User')->join('Device', fn(JoinBuilder $join) => $join->on('User.id', 'Device.userId'))->toString();
        $this->assertSame("SELECT * FROM `User` JOIN `Device` ON `User`.`id` = `Device`.`userId`", $sql);
    }

    public function test_join_using_on_and_where(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')
            ->join('Device AS d', fn(JoinBuilder $join) => $join->on('u.id', 'd.userId'))
            ->where('id', [1,2])
            ->toString();
        $this->assertSame("SELECT * FROM `User` AS `u` JOIN `Device` AS `d` ON `u`.`id` = `d`.`userId` WHERE `id` IN (1, 2)", $sql);
    }

    public function test_joinOn(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->joinOn('Device AS d', 'u.id', 'd.userId')->toString();
        $this->assertSame("SELECT * FROM `User` AS `u` JOIN `Device` AS `d` ON `u`.`id` = `d`.`userId`", $sql);
    }

    public function test_lockForUpdate(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate()->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 FOR UPDATE", $sql);
    }

    public function test_lockForUpdate_with_option_nowait(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::Nowait)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 FOR UPDATE NOWAIT", $sql);
    }

    public function test_lockForUpdate_with_option_skip_locked(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forUpdate(LockOption::SkipLocked)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 FOR UPDATE SKIP LOCKED", $sql);
    }

    public function test_lockForShare(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->forShare()->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 FOR SHARE", $sql);
    }

    public function test_where_with_two_args(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', [3, 4])->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` IN (3, 4)", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', Bounds::closed(1, 2))->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` >= 1 AND `id` <= 2", $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', 1)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_where_with_three_args(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', eq: 1)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_where_multiples(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->where('status', 0)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 AND `status` = 0", $sql);
    }

    public function test_where_combined(): void
    {
        $sql = $this->selectBuilder()->from('User')
            ->where(ConditionBuilder::for('id')->lessThan(1)->or()->equals(3))
            ->where('id', not: -1)
            ->toString();
        $this->assertSame("SELECT * FROM `User` WHERE (`id` < 1 OR `id` = 3) AND `id` != -1", $sql);
    }

    public function test_where_column(): void
    {
        $sql = $this->selectBuilder()->from('User')->whereColumn('User.id', 'Device.userId')->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `User`.`id` = `Device`.`userId`", $sql);
    }

    public function test_where_column_aliased(): void
    {
        $sql = $this->selectBuilder()->from('User AS u', 'Device AS d')->whereColumn('u.id', 'd.userId')->toString();
        $this->assertSame("SELECT * FROM `User` AS `u`, `Device` AS `d` WHERE `u`.`id` = `d`.`userId`", $sql);
    }

    public function test_where_tuple(): void
    {
        $sql = $this->selectBuilder()->from('User')->where(['id', 'status'], [[1, 1], [2, 3]])->toString();
        $this->assertSame("SELECT * FROM `User` WHERE (`id`, `status`) IN ((1, 1), (2, 3))", $sql);
    }

    public function test_orderBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderBy('id')->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` ASC", $sql);
    }

    public function test_orderByDesc(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 ORDER BY `id` DESC", $sql);
    }

    public function test_groupBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->groupBy('status')->toString();
        $this->assertSame("SELECT * FROM `User` GROUP BY `status`", $sql);
    }

    public function test_reorder(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->reorder()->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1", $sql);
    }

    public function test_where_and_limit(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1", $sql);
    }

    public function test_where_and_offset(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->offset(10)->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 LIMIT 1 OFFSET 10", $sql);
    }

    public function test_combination(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->groupBy('status')->having('status', 1)->limit(2)->orderBy('id')->toString();
        $this->assertSame("SELECT * FROM `User` WHERE `id` = 1 GROUP BY `status` HAVING `status` = 1 ORDER BY `id` ASC LIMIT 2", $sql);
    }

    public function test_clone(): void
    {
        $where = ConditionBuilder::for('id')->equals(1)->or('id')->equals(2);
        $base = $this->selectBuilder()->from('User')->where($where);
        $copy = clone $base;
        $where->or()->in([3,4]); // change $base but should not be reflected on copy
        $this->assertSame("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2 OR `id` IN (3, 4))", $base->toString());
        $this->assertSame("SELECT * FROM `User` WHERE (`id` = 1 OR `id` = 2)", $copy->toString());
        $this->assertNotSame($base->toString(), $copy->toString());
    }

    public function test_cast_to_time_from_string(): void
    {
        $casting = new Raw('"2020-01-01" as c');
        $result = $this->selectBuilder()->columns($casting)->cast('c', Time::class)->execute();
        $value = $result->single()->c;
        $this->assertInstanceOf(Time::class, $value);
        $this->assertSame('2020-01-01 00:00:00', $value->format('Y-m-d H:i:s'));
    }

    public function test_cast_to_int_backed_enum(): void
    {
        $casting = new Raw('2 as c');
        $result = $this->selectBuilder()->columns($casting)->cast('c', IntCastEnum::class)->execute();
        $value = $result->single()->c;
        $this->assertSame(IntCastEnum::B, $value);
        $this->assertSame(2, $value->value);
    }

    public function test_casts_to_different_casts(): void
    {
        $castings = [new Raw('"2020-01-01" as c1'), new Raw('2 as c2')];
        $result = $this->selectBuilder()->columns(...$castings)->casts([
            'c1' => Time::class,
            'c2' => IntCastEnum::class,
        ])->execute();
        $this->assertSame('2020-01-01 00:00:00', $result->single()->c1->format('Y-m-d H:i:s'));
        $this->assertSame(IntCastEnum::B, $result->single()->c2);
    }
}
