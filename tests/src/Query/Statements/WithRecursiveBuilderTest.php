<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Raw;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function implode;

class WithRecursiveBuilderTest extends QueryTestCase
{
    protected string $useConnection = 'mysql';

    public function test_withRecursive__define_single(): void
    {
        $handler = $this->connect()->query();
        $query = $handler
            ->withRecursive('cte', ['n'], function(SelectBuilder $select) use ($handler) {
                $select(new Raw(1))
                    ->unionAll($handler
                        ->select(new Raw('n + 1'))
                        ->from('cte')
                        ->where('n', lt: 3));
            })
            ->select()
            ->from('cte');

        $this->assertSame("WITH RECURSIVE `cte` (`n`) AS ((SELECT 1) UNION ALL (SELECT n + 1 FROM `cte` WHERE `n` < 3)) SELECT * FROM `cte`", $query->toSql());
        $this->assertSame([1, 2, 3], $query->pluck('n')->all());
    }

    public function test_withRecursive__define_multiple(): void
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
            ->from('cte1')
            ->joinOn('cte2', 'cte1.n', 'cte2.m');

        $this->assertSame(implode('', [
            "WITH RECURSIVE `cte1` (`n`) AS ((SELECT 1) UNION ALL (SELECT n + 1 FROM `cte1` WHERE `n` < 3)), ",
                           "`cte2` (`m`) AS ((SELECT 1) UNION ALL (SELECT m + 1 FROM `cte2` WHERE `m` < 3)) ",
            "SELECT * FROM `cte1` JOIN `cte2` ON `cte1`.`n` = `cte2`.`m`",
        ]), $query->toSql());
        $this->assertSame([1, 2, 3], $query->pluck('n')->all());
    }
}
