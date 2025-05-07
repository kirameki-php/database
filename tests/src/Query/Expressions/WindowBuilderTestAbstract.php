<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Expressions;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Expressions\RowNumber;
use Kirameki\Database\Query\Expressions\Sum;
use Kirameki\Database\Query\Statements\SortOrder;
use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class WindowBuilderTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function connect(): Connection
    {
        return $this->createTempConnection($this->useConnection);
    }

    public function test_partitionBy(): void
    {
        $column = new RowNumber()->over()->partitionBy('a', 'b');
        $query = $this->connect()->query()->select($column);
        $this->assertSameSql('SELECT ROW_NUMBER() OVER (PARTITION BY "a", "b") AS "row"', $query);
    }

    public function test_orderBy(): void
    {
        $column = new RowNumber()->over()->orderBy('a', SortOrder::Descending);
        $query = $this->connect()->query()->select($column);
        $this->assertSameSql('SELECT ROW_NUMBER() OVER (ORDER BY "a" DESC) AS "row"', $query);
    }

    public function test_orderByAsc(): void
    {
        $column = new RowNumber()->over()->orderByAsc('a');
        $query = $this->connect()->query()->select($column);
        $this->assertSameSql('SELECT ROW_NUMBER() OVER (ORDER BY "a") AS "row"', $query);
    }

    public function test_orderByDesc(): void
    {
        $column = new RowNumber()->over()->orderByDesc('a');
        $query = $this->connect()->query()->select($column);
        $this->assertSameSql('SELECT ROW_NUMBER() OVER (ORDER BY "a" DESC) AS "row"', $query);
    }

    public function test_with_partition_and_order(): void
    {
        $column = new RowNumber()->over()->partitionBy('a', 'b')->orderBy('c');
        $query = $this->connect()->query()->select($column);
        $this->assertSameSql('SELECT ROW_NUMBER() OVER (PARTITION BY "a", "b" ORDER BY "c") AS "row"', $query);
    }

    public function test_passing_query_function(): void
    {
        $sum = new Sum();
        $sum->over()->orderBy('c');
        $query = $this->connect()->query()->select($sum);
        $this->assertSameSql('SELECT SUM(*) OVER (ORDER BY "c") AS "sum"', $query);
    }
}
