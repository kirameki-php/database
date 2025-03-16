<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Raw;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function dump;

class WithBuilderTest extends QueryTestCase
{
    protected string $useConnection = 'mysql';

    public function test_with__define_single(): void
    {
        $handler = $this->connect()->query();
        $query = $handler
            ->with('cte', [], fn(SelectBuilder $select) => $select(new Raw('5 AS a')))
            ->select()
            ->from('cte');

        $this->assertSame("WITH `cte` AS (SELECT 5 AS a) SELECT * FROM `cte`", $query->toSql());
        $this->assertSame([5], $query->pluck('a')->all());
    }

    public function test_with__define_multiple(): void
    {
        $handler = $this->connect()->query();
        $query = $handler
            ->with('cte1', ['a', 'b'], fn(SelectBuilder $select) => $select(new Raw(5), new Raw(6)))
            ->with('cte2', ['a', 'b'], fn(SelectBuilder $select) => $select(new Raw(5), new Raw(6)))
            ->select()->from('cte1')
            ->unionAll($handler->select()->from('cte2'));

        dump($query->toSql());
        $this->assertSame([5], $query->pluck('a')->all());
    }
}
