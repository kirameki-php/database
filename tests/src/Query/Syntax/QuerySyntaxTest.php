<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Syntax;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\NullOrder;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Query\Statements\TagsFormat;
use Tests\Kirameki\Database\Query\QueryTestCase;

class QuerySyntaxTest extends QueryTestCase
{
    public function test_interpolate_basic(): void
    {
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?', [1]);
        $handler = $this->sqliteConnection()->query();
        $this->assertSame('SELECT * FROM `a` WHERE id = 1', $handler->toSql($statement));
    }

    public function test_interpolate_with_escape_character(): void
    {
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = \'??\' AND id = ?', ['a']);
        $this->assertSame('SELECT * FROM `a` WHERE id = \'??\' AND id = \'a\'', $handler->toSql($statement));
    }

    public function test_interpolate_with_escape_character_and_parameter(): void
    {
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = \'???\'', [1]);
        $this->assertSame('SELECT * FROM `a` WHERE id = \'??1\'', $handler->toSql($statement));
    }

    public function test_interpolate_too_many_parameters(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of parameters given for query. (query: SELECT * FROM `a` WHERE id = ?, remains: 1)');
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?', [1, 2]);
        $handler->toSql($statement);
    }

    public function test_interpolate_not_enough_parameters(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of parameters given for query. (query: SELECT * FROM `a` WHERE id = ?, remains: -1)');
        $handler = $this->sqliteConnection()->query();
        $statement = new RawStatement('SELECT * FROM `a` WHERE id = ?');
        $handler->toSql($statement);
    }

    public function test_where_raw(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->whereRaw('id1 = id2');
        $this->assertSame('SELECT * FROM "t" WHERE id1 = id2', $q->toSql());
    }

    public function test_where_raw_invalid_value(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Invalid Raw value. Expected: Expression. Got: int.');
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->whereRaw('id');
        $q->statement->where[0]->value = 1;
        $q->toSql();
    }

    public function test_where_eq(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', eq: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1', $q->toSql());
    }

    public function test_where_eq_null(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', eq: null);
        $this->assertSame('SELECT * FROM "t" WHERE "id" IS NULL', $q->toSql());
    }

    public function test_where_ne(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', ne: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" != 1', $q->toSql());
    }

    public function test_where_ne_null(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', ne: null);
        $this->assertSame('SELECT * FROM "t" WHERE "id" IS NOT NULL', $q->toSql());
    }

    public function test_where_gte(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', gte: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" >= 1', $q->toSql());
    }

    public function test_where_gt(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', gt: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" > 1', $q->toSql());
    }

    public function test_where_lte(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', lte: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" <= 1', $q->toSql());
    }

    public function test_where_lt(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', lt: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" < 1', $q->toSql());
    }

    public function test_where_in(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', in: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" IN (1, 2)', $q->toSql());
    }

    public function test_where_in_empty(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', in: []);
        $this->assertSame('SELECT * FROM "t" WHERE 1 = 0', $q->toSql());
    }

    public function test_where_in_invalid(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Value for WHERE IN. Expected: iterable|SelectStatement. Got: int.');
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', in: []);
        $statement = $q->statement;
        $statement->where[0]->value = 1;
        $q->toSql();
    }

    public function test_where_in_subquery(): void
    {
        $handler = $this->sqliteConnection()->query();
        $sub = $handler->select()->from('t2')->where('id', 1);
        $q = $handler->select()->from('t')->where('id', in: $sub);
        $this->assertSame('SELECT * FROM "t" WHERE "id" IN (SELECT * FROM "t2" WHERE "id" = 1)', $q->toSql());
    }

    public function test_where_not_in(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', notIn: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" NOT IN (1, 2)', $q->toSql());
    }

    public function test_where_not_in_empty(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', notIn: []);
        $this->assertSame('SELECT * FROM "t" WHERE 1 = 0', $q->toSql());
    }

    public function test_where_between(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', between: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" BETWEEN 1 AND 2', $q->toSql());
    }

    public function test_where_not_between(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', notBetween: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "id" NOT BETWEEN 1 AND 2', $q->toSql());
    }

    public function test_where_like(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('name', like: 'a%');
        $this->assertSame('SELECT * FROM "t" WHERE "name" LIKE \'a%\'', $q->toSql());
    }

    public function test_where_not_like(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('name', notLike: 'a%');
        $this->assertSame('SELECT * FROM "t" WHERE "name" NOT LIKE \'a%\'', $q->toSql());
    }

    public function test_where_in_range(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', Bounds::excluded(1, 2));
        $this->assertSame('SELECT * FROM "t" WHERE "id" > 1 AND "id" < 2', $q->toSql());
    }

    public function test_where_in_range__invalid_value(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Value for WHERE with range. Expected: Bounds. Got: int.');
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', Bounds::excluded(1, 2));
        $q->statement->where[0]->value = 1;
        $q->toSql();
    }

    public function test_where_not_in_range(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', not: Bounds::excluded(1, 2));
        $this->assertSame('SELECT * FROM "t" WHERE "id" <= 1 OR "id" >= 2', $q->toSql());
    }

    public function test_where_exists(): void
    {
        $handler = $this->sqliteConnection()->query();
        $sub = $handler->select()->from('t2')->where('id', 1);
        $q = $handler->select()->from('t')->whereExists($sub);
        $this->assertSame('SELECT * FROM "t" WHERE EXISTS (SELECT * FROM "t2" WHERE "id" = 1)', $q->toSql());
    }

    public function test_where_exists__invalid_value(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Value for WHERE EXISTS. Expected: SelectStatement. Got: int.');
        $handler = $this->sqliteConnection()->query();
        $sub = $handler->select()->from('t2')->where('id', 1);
        $q = $handler->select()->from('t')->whereExists($sub);
        $q->statement->where[0]->value = 1;
        $q->toSql();
    }

    public function test_where_not_exists(): void
    {
        $handler = $this->sqliteConnection()->query();
        $sub = $handler->select()->from('t2')->where('id', 1);
        $q = $handler->select()->from('t')->whereNotExists($sub);
        $this->assertSame('SELECT * FROM "t" WHERE NOT EXISTS (SELECT * FROM "t2" WHERE "id" = 1)', $q->toSql());
    }

    public function test_groupBy_single_column(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->groupBy('id');
        $this->assertSame('SELECT * FROM "t" GROUP BY "id"', $q->toSql());
    }

    public function test_groupBy_multiple_column(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->groupBy('id', 'name');
        $this->assertSame('SELECT * FROM "t" GROUP BY "id", "name"', $q->toSql());
    }

    public function test_having(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->groupBy('id')->having('id', eq: 1);
        $this->assertSame('SELECT * FROM "t" GROUP BY "id" HAVING "id" = 1', $q->toSql());
    }

    public function test_orderBy_single_column(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->orderBy('id');
        $this->assertSame('SELECT * FROM "t" ORDER BY "id"', $q->toSql());
    }

    public function test_orderBy_single_column_desc(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->orderByDesc('id');
        $this->assertSame('SELECT * FROM "t" ORDER BY "id" DESC', $q->toSql());
    }

    public function test_orderBy_multiple_column(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->orderBy('id')->orderByDesc('name');
        $this->assertSame('SELECT * FROM "t" ORDER BY "id", "name" DESC', $q->toSql());
    }

    public function test_orderBy_null_first(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->orderBy('id', nulls: NullOrder::First);
        $this->assertSame('SELECT * FROM "t" ORDER BY "id"', $q->toSql());
    }

    public function test_orderBy_null_last(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->orderBy('id', nulls: NullOrder::Last);
        $this->assertSame('SELECT * FROM "t" ORDER BY "id" NULLS LAST', $q->toSql());
    }

    public function test_orderBy_full_spec(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->orderBy('id', SortOrder::Descending, nulls: NullOrder::Last);
        $this->assertSame('SELECT * FROM "t" ORDER BY "id" DESC NULLS LAST', $q->toSql());
    }

    public function test_limit(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->limit(10);
        $this->assertSame('SELECT * FROM "t" LIMIT 10', $q->toSql());
    }

    public function test_offset(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->offset(10);
        $this->assertSame('SELECT * FROM "t" OFFSET 10', $q->toSql());
    }

    public function test_limit_and_offset(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->limit(10)->offset(10);
        $this->assertSame('SELECT * FROM "t" LIMIT 10 OFFSET 10', $q->toSql());
    }

    public function test_returning_no_column(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->insertInto('t')->value(['id' => 1, 'name' => 'a'])->returning();
        $this->assertSame('INSERT INTO "t" ("id", "name") VALUES (1, \'a\') RETURNING *', $q->toSql());
    }

    public function test_returning_single_column(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->insertInto('t')->value(['id' => 1, 'name' => 'a'])->returning('id');
        $this->assertSame('INSERT INTO "t" ("id", "name") VALUES (1, \'a\') RETURNING "id"', $q->toSql());
    }

    public function test_returning_multiple_column(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->insertInto('t')->value(['id' => 1, 'name' => 'a'])->returning('id', 'name');
        $this->assertSame('INSERT INTO "t" ("id", "name") VALUES (1, \'a\') RETURNING "id", "name"', $q->toSql());
    }

    public function test_formatTags_as_log(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t')->where('id', 1)->setTag('t', 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1 /* t=1 */', $q->toSql());
    }

    public function test_formatTags_as_open_telemetry(): void
    {
        $config = new DatabaseConfig([], tagsFormat: TagsFormat::OpenTelemetry);
        $adapter = new SqliteAdapter($config, new SqliteConfig(':memory:'));
        $handler = $this->createTempConnection('sqlite', $adapter)->query();
        $q = $handler->select()->from('t')->where('id', 1)->setTag('t', 1);
        $this->assertSame('SELECT * FROM "t" WHERE "id" = 1 /* t=\'1\' */', $q->toSql());
    }

    public function test_asTable_basic(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t');
        $this->assertSame('SELECT * FROM "t"', $q->toSql());
    }

    public function test_asTable_with_alias(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('t as a');
        $this->assertSame('SELECT * FROM "t" AS "a"', $q->toSql());
    }

    public function test_asTable_with_database(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('db.t');
        $this->assertSame('SELECT * FROM "db"."t"', $q->toSql());
    }

    public function test_asTable_with_alias_and_database(): void
    {
        $handler = $this->sqliteConnection()->query();
        $q = $handler->select()->from('db.t as a');
        $this->assertSame('SELECT * FROM "db"."t" AS "a"', $q->toSql());
    }
}
