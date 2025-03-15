<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Raw;
use Tests\Kirameki\Database\Query\QueryTestCase;

class WithBuilderTest extends QueryTestCase
{
    protected string $useConnection = 'mysql';

    public function test_withRecursive(): void
    {
        $handler = $this->connect()->query();
        $query = $handler
            ->withRecursive('cte1', ['n'], function(SelectBuilder $select) use ($handler) {
                $select(new Raw(1))
                    ->unionAll($handler
                        ->select(new Raw('n + 1'))
                        ->from('cte1')
                        ->where('n', lt: 3));
            })
            ->withRecursive('cte2', ['m'], function(SelectBuilder $select) use ($handler) {
                $select(new Raw(1))
                    ->unionAll($handler
                        ->select(new Raw('m + 1'))
                        ->from('cte2')
                        ->where('m', lt: 3));
            })
            ->select()
            ->from('cte1', 'cte2');

        $this->assertSame("WITH RECURSIVE `cte` (`n`) AS ((SELECT 1) UNION ALL (SELECT n + 1 FROM `cte` WHERE `n` < 3)) SELECT * FROM `cte`", $query->toSql());
        $this->assertSame([1, 2, 3], $query->pluck('n')->all());
    }
}
