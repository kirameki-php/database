<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Exceptions\InvalidArgumentException;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Raw;
use LogicException;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function implode;

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

        $this->assertSame('WITH "cte" AS (SELECT 5 AS a) SELECT * FROM "cte"', $query->toSql());
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

        $this->assertSame(implode('', [
            'WITH "cte1" ("a", "b") AS (SELECT 5, 6), ',
            '"cte2" ("a", "b") AS (SELECT 5, 6) ',
            '(SELECT * FROM "cte1") UNION ALL (SELECT * FROM "cte2")',
        ]), $query->toSql());
        $this->assertSame([5, 5], $query->pluck('a')->all());
    }

    public function test_with__using_raw(): void
    {
        $handler = $this->connect()->query();
        $query = $handler
            ->with('cte', [], $handler->raw('SELECT 5 AS a'))
            ->select()
            ->from('cte');

        $this->assertSame('WITH "cte" AS (SELECT 5 AS a) SELECT * FROM "cte"', $query->toSql());
        $this->assertSame([5], $query->pluck('a')->all());
    }

    public function test_with__using_invalid_statement(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid CTE statement: ' . InsertStatement::class);

        $handler = $this->connect()->query();
        $handler->with('cte', as: $handler->insertInto('User')->values([['id' => 1]]))
            ->select()
            ->from('cte')
            ->toSql();
    }

    public function test_with__without_as(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "as" argument must be provided.');

        $handler = $this->connect()->query();
        $handler->with('cte', [])
            ->select()
            ->from('cte')
            ->toSql();
    }
}
