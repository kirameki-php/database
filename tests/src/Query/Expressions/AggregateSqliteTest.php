<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Expressions;

use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Query\Expressions\Avg;
use Kirameki\Database\Query\Expressions\Count;
use Kirameki\Database\Query\Expressions\Max;
use Kirameki\Database\Query\Expressions\Min;
use Kirameki\Database\Query\Expressions\Sum;
use const PHP_INT_MAX;

class AggregateSqliteTest extends AggregateTestAbstract
{
    protected string $useConnection = 'sqlite';

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

        $expr = Min::column('id');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MIN("id") AS "min" FROM "t"', $query->toString());
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

        $expr = Min::column('id', '_min_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MIN("id") AS "_min_" FROM "t"', $query->toString());
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

        $expr = Max::column('id');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MAX("id") AS "max" FROM "t"', $query->toString());
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

        $expr = Max::column('id', '_max_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT MAX("id") AS "_max_" FROM "t"', $query->toString());
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

        $expr = Count::column('*');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT COUNT(*) AS "count" FROM "t"', $query->toString());
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

        $expr = Count::column('*', '_cnt_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT COUNT(*) AS "_cnt_" FROM "t"', $query->toString());
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

        $expr = Avg::column('id');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT AVG("id") AS "avg" FROM "t"', $query->toString());
        $this->assertSame(20.0, $query->value('avg'));
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

        $expr = Avg::column('id', '_avg_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT AVG("id") AS "_avg_" FROM "t"', $query->toString());
        $this->assertSame(20.0, $query->value('_avg_'));
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

        $expr = Sum::column('id');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT SUM("id") AS "sum" FROM "t"', $query->toString());
        $this->assertSame(60, $query->value('sum'));
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

        $expr = Sum::column('id', '_sum_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT SUM("id") AS "_sum_" FROM "t"', $query->toString());
        $this->assertSame(60, $query->value('_sum_'));
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

        $expr = Sum::column('id', '_sum_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT SUM("id") AS "_sum_" FROM "t"', $query->toString());

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[HY000]: General error: 1 integer overflow');
        $query->value('_sum_');
    }
}
