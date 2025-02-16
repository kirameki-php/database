<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\TagsFormat;
use Tests\Kirameki\Database\Query\QueryTestCase;

class QuerySyntaxTest extends QueryTestCase
{
    public function test_interpolate_basic(): void
    {
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?', [1]);
        $handler = $this->sqliteConnection()->query();
        $this->assertSame('SELECT * FROM `a` WHERE id = 1', $handler->toString($statement));
    }

    public function test_interpolate_with_escape_character(): void
    {
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = \'??\' AND id = ?', ['a']);
        $this->assertSame('SELECT * FROM `a` WHERE id = \'??\' AND id = \'a\'', $handler->toString($statement));
    }

    public function test_interpolate_with_escape_character_and_parameter(): void
    {
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = \'???\'', [1]);
        $this->assertSame('SELECT * FROM `a` WHERE id = \'??1\'', $handler->toString($statement));
    }

    public function test_interpolate_too_many_parameters(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of parameters given for query. (query: SELECT * FROM `a` WHERE id = ?, remains: 1)');
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?', [1, 2]);
        $handler->toString($statement);
    }

    public function test_interpolate_not_enough_parameters(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of parameters given for query. (query: SELECT * FROM `a` WHERE id = ?, remains: -1)');
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?');
        $handler->toString($statement);
    }

    public function test_formatTags_as_log(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', 1)->setTag('t', 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1 /* t=1 */', $q->toString());
    }

    public function test_formatTags_as_open_telemetry(): void
    {
        $config = new DatabaseConfig([], tagsFormat: TagsFormat::OpenTelemetry);
        $adapter = new SqliteAdapter($config, new SqliteConfig(':memory:'));
        $handler = $this->createTempConnection('sqlite', $adapter)->query();
        $q = $handler->select()->from('t')->where('id', 1)->setTag('t', 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1 /* t=\'1\' */', $q->toString());
    }

    public function test_where_eq(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', eq: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1', $q->toString());
    }

    public function test_where_ne(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', ne: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" != 1', $q->toString());
    }

    public function test_where_gte(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', gte: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" >= 1', $q->toString());
    }

    public function test_where_gt(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', gt: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" > 1', $q->toString());
    }

    public function test_where_lte(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', lte: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" <= 1', $q->toString());
    }

    public function test_where_lt(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', lt: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" < 1', $q->toString());
    }

    public function test_where_in(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', in: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" IN (1, 2)', $q->toString());
    }

    public function test_where_in_empty(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', in: []);
        $this->assertSame('SELECT * FROM "t" WHERE 1 = 0', $q->toString());
    }

    public function test_where_in_invalid(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Value for WHERE IN. Expected: iterable|SelectStatement. Got: int.');
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', in: []);
        $statement = $q->getStatement();
        $statement->where[0]->value = 1;
        $q->toString();
    }

    public function test_where_in_subquery(): void
    {
        $handler = $this->sqliteConnection()->query();
        $sub = $handler->select()->from('t2')->where('id', 1);
        $q = $handler->select()->from('t')->where('id', in: $sub);
        $this->assertSame('SELECT * FROM "t" WHERE "id" IN (SELECT * FROM "t2" WHERE "id" = 1)', $q->toString());
    }

    public function test_where_not_in(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', notIn: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" NOT IN (1, 2)', $q->toString());
    }

    public function test_where_not_in_empty(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', notIn: []);
        $this->assertSame('SELECT * FROM "t" WHERE 1 = 0', $q->toString());
    }

    public function test_where_between(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', between: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" BETWEEN 1 AND 2', $q->toString());
    }

    public function test_where_not_between(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', notBetween: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" NOT BETWEEN 1 AND 2', $q->toString());
    }
}
