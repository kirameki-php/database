<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Expressions;

use Kirameki\Database\Query\Expressions\Avg;
use Kirameki\Database\Query\Expressions\Count;
use Kirameki\Database\Query\Expressions\Max;
use Kirameki\Database\Query\Expressions\Min;
use Kirameki\Database\Query\Expressions\Sum;
use const PHP_INT_MAX;

class AggregateMySqlTest extends AggregateTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_aggregate_min(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 3])
            ->value(['id' => 2])
            ->execute();

        $expr = new Min('id');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MIN(`id`) AS `min` FROM `t`', $query->toString());
        $this->assertSame(2, $query->value('min'));
    }

    public function test_aggregate_min__with_custom_as_name(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 3])
            ->value(['id' => 2])
            ->execute();

        $expr = new Min('id', '_min_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MIN(`id`) AS `_min_` FROM `t`', $query->toString());
        $this->assertSame(2, $query->value('_min_'));
    }

    public function test_aggregate_max(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 2])
            ->value(['id' => 3])
            ->execute();

        $expr = new Max('id');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MAX(`id`) AS `max` FROM `t`', $query->toString());
        $this->assertSame(3, $query->value('max'));
    }

    public function test_aggregate_max__with_custom_as_name(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 2])
            ->value(['id' => 3])
            ->execute();

        $expr = new Max('id', '_max_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MAX(`id`) AS `_max_` FROM `t`', $query->toString());
        $this->assertSame(3, $query->value('_max_'));
    }

    public function test_aggregate_count(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 1])
            ->value(['id' => 2])
            ->value(['id' => 3])
            ->execute();

        $query = $connection->query()
            ->select(new Count())
            ->from('t');

        $this->assertSame('SELECT COUNT(*) AS `count` FROM `t`', $query->toString());
        $this->assertSame(3, $query->value('count'));
    }

    public function test_aggregate_count__with_custom_as_name(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 1])
            ->value(['id' => 2])
            ->value(['id' => 3])
            ->execute();

        $query = $connection->query()
            ->select(new Count('*', '_cnt_'))
            ->from('t');

        $this->assertSame('SELECT COUNT(*) AS `_cnt_` FROM `t`', $query->toString());
        $this->assertSame(3, $query->value('_cnt_'));
    }

    public function test_aggregate_avg(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 10])
            ->value(['id' => 20])
            ->value(['id' => 30])
            ->execute();

        $query = $connection->query()
            ->select(new Avg('id'))
            ->from('t');

        $this->assertSame('SELECT AVG(`id`) AS `avg` FROM `t`', $query->toString());
        $this->assertSame('20.0000', $query->value('avg'));
    }

    public function test_aggregate_avg__with_custom_as_name(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 10])
            ->value(['id' => 20])
            ->value(['id' => 30])
            ->execute();

        $query = $connection->query()
            ->select(new Avg('id', '_avg_'))
            ->from('t');

        $this->assertSame('SELECT AVG(`id`) AS `_avg_` FROM `t`', $query->toString());
        $this->assertSame('20.0000', $query->value('_avg_'));
    }

    public function test_aggregate_sum(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 10])
            ->value(['id' => 20])
            ->value(['id' => 30])
            ->execute();

        $query = $connection->query()
            ->select(Sum::column('id'))
            ->from('t');

        $this->assertSame('SELECT SUM(`id`) AS `sum` FROM `t`', $query->toString());
        $this->assertSame('60', $query->value('sum'));
    }

    public function test_aggregate_sum__with_custom_as_name(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => 10])
            ->value(['id' => 20])
            ->value(['id' => 30])
            ->execute();

        $query = $connection->query()
            ->select(new Sum('id', '_sum_'))
            ->from('t');

        $this->assertSame('SELECT SUM(`id`) AS `_sum_` FROM `t`', $query->toString());
        $this->assertSame('60', $query->value('_sum_'));
    }

    public function test_aggregate_sum__with_total_larger_than_int64(): void
    {
        $connection = $this->connect();
        $table = $connection->schema()->createTable('t');
        $table->int('id')->primaryKey();
        $table->execute();
        $connection->query()->insertInto('t')
            ->value(['id' => PHP_INT_MAX])
            ->value(['id' => PHP_INT_MAX - 1])
            ->execute();

        $query = $connection->query()
            ->select(new Sum('id', '_sum_'))
            ->from('t');

        $this->assertSame('SELECT SUM(`id`) AS `_sum_` FROM `t`', $query->toString());
        $this->assertSame('18446744073709551613', $query->value('_sum_'));
    }
}
