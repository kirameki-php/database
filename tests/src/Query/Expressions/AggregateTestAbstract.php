<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Expressions;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Expressions\Aggregate;
use Tests\Kirameki\Database\Query\QueryTestCase;

class AggregateTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function connect(): Connection
    {
        return $this->createTempConnection($this->useConnection);
    }

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

        $expr = Aggregate::min('id');
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

        $expr = Aggregate::min('id', '_min_');
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

        $expr = Aggregate::max('id');
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

        $expr = Aggregate::max('id', '_max_');
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

        $expr = Aggregate::count('*');
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

        $expr = Aggregate::count('*', '_cnt_');
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

        $expr = Aggregate::avg('id');
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

        $expr = Aggregate::avg('id', '_avg_');
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

        $expr = Aggregate::sum('id');
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

        $expr = Aggregate::sum('id', '_sum_');
        $query = $connection->query()->select($expr)->from('t');

        $this->assertSame('SELECT SUM("id") AS "_sum_" FROM "t"', $query->toString());
        $this->assertSame(60, $query->value('_sum_'));
    }
}