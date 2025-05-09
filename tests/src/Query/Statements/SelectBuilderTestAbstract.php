<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Collections\Exceptions\CountMismatchException;
use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Query\Pagination\CursorPaginator;
use Kirameki\Database\Query\Pagination\OffsetPaginator;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\JoinBuilder;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\Tuple;
use Kirameki\Database\Raw;
use stdClass;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function array_map;
use function iterator_to_array;
use function range;

abstract class SelectBuilderTestAbstract extends QueryTestCase
{
    public function test_plain(): void
    {
        $sql = $this->selectBuilder()->columns(new Raw('1'))->toSql();
        $this->assertSame("SELECT 1", $sql);
    }

    public function test_from(): void
    {
        $sql = $this->selectBuilder()->from('User')->toSql();
        $this->assertSame('SELECT * FROM "User"', $sql);
    }

    public function test_from__with_alias(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->toSql();
        $this->assertSame('SELECT * FROM "User" AS "u"', $sql);
    }

    public function test_from__multiple_tables(): void
    {
        $sql = $this->selectBuilder()->from('User AS u', 'UserItem')->toSql();
        $this->assertSame('SELECT * FROM "User" AS "u", "UserItem"', $sql);
    }

    public function test_from__with_multiple_where_column(): void
    {
        $sql = $this->selectBuilder()
            ->columns('User.*')
            ->from('User', 'UserItem')
            ->whereColumn('User.id', 'UserItem.userId')
            ->toSql();
        $this->assertSame('SELECT "User".* FROM "User", "UserItem" WHERE "User"."id" = "UserItem"."userId"', $sql);
    }

    public function test_from__with_expression(): void
    {
        $sql = $this->selectBuilder()->from(new Raw('User'))->toSql();
        $this->assertSame("SELECT * FROM User", $sql);
    }

    public function test_columns(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id', 'name')->toSql();
        $this->assertSame('SELECT "id", "name" FROM "User"', $sql);
    }

    public function test_columns__with_alias(): void
    {
        $sql = $this->selectBuilder()->from('User as u')->columns('u.*', 'u.name')->toSql();
        $this->assertSame('SELECT "u".*, "u"."name" FROM "User" AS "u"', $sql);
    }

    public function test_columns__with_alias_embedded(): void
    {
        $sql = $this->selectBuilder()->from('User as u')->columns('u.name as u_name')->toSql();
        $this->assertSame('SELECT "u"."name" AS "u_name" FROM "User" AS "u"', $sql);
    }

    public function test_distinct(): void
    {
        $sql = $this->selectBuilder()->from('User')->columns('id')->distinct()->toSql();
        $this->assertSame('SELECT DISTINCT "id" FROM "User"', $sql);
    }
    
    abstract public function test_forceIndex(): void;

    public function test_join_using_on(): void
    {
        $sql = $this->selectBuilder()->from('User')->join('Device', fn(JoinBuilder $join) => $join->on('User.id', 'Device.userId'))->toSql();
        $this->assertSame('SELECT * FROM "User" JOIN "Device" ON "User"."id" = "Device"."userId"', $sql);
    }

    public function test_join_using_on_and_where(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')
            ->join('Device AS d', fn(JoinBuilder $join) => $join->on('u.id', 'd.userId'))
            ->where('id', [1,2])
            ->toSql();
        $this->assertSame('SELECT * FROM "User" AS "u" JOIN "Device" AS "d" ON "u"."id" = "d"."userId" WHERE "id" IN (1, 2)', $sql);
    }

    public function test_joinOn(): void
    {
        $sql = $this->selectBuilder()->from('User AS u')->joinOn('Device AS d', 'u.id', 'd.userId')->toSql();
        $this->assertSame('SELECT * FROM "User" AS "u" JOIN "Device" AS "d" ON "u"."id" = "d"."userId"', $sql);
    }

    abstract public function test_lockForUpdate(): void;

    abstract public function test_lockForUpdate_with_option_nowait(): void;

    abstract public function test_lockForUpdate_with_option_skip_locked(): void;

    abstract public function test_lockForShare(): void;


    public function test_where__with_two_args(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', [3, 4])->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" IN (3, 4)', $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', Bounds::closed(1, 2))->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" >= 1 AND "id" <= 2', $sql);

        $sql = $this->selectBuilder()->from('User')->where('id', 1)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $sql);
    }

    public function test_where__with_two_args_named_operator_ne(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', ne: 1)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" != 1', $sql);
    }

    public function test_where__multiples(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->where('status', 0)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 AND "status" = 0', $sql);
    }

    public function test_where__combined(): void
    {
        $sql = $this->selectBuilder()->from('User')
            ->where(fn(ConditionBuilder $q) => $q('id', lt: 1)->or('id', 3))
            ->where('id', not: -1)
            ->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE ("id" < 1 OR "id" = 3) AND "id" != -1', $sql);
    }

    public function test_where__with_nested_nesting(): void
    {
        $sql = $this->selectBuilder()->from('User')
            ->where(function(ConditionBuilder $q) {
                $q('id', 1)->or(function(ConditionBuilder $q) {
                    $q->or('id', 2)->and('id', 3);
                });
            })
            ->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE ("id" = 1 OR ("id" = 2 AND "id" = 3))', $sql);
    }

    public function test_whereColumn(): void
    {
        $sql = $this->selectBuilder()->from('User')->whereColumn('User.id', 'Device.userId')->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "User"."id" = "Device"."userId"', $sql);
    }

    public function test_whereColumn__aliased(): void
    {
        $sql = $this->selectBuilder()->from('User AS u', 'Device AS d')->whereColumn('u.id', 'd.userId')->toSql();
        $this->assertSame('SELECT * FROM "User" AS "u", "Device" AS "d" WHERE "u"."id" = "d"."userId"', $sql);
    }

    public function test_where__tuple(): void
    {
        $sql = $this->selectBuilder()->from('User')->where(new Tuple('id', 'status'), new Tuple([1, 1], [2, 3]))->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE ("id", "status") IN ((1, 1), (2, 3))', $sql);
    }

    public function test_where__and__from_two_wheres(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->where('status', not: 0)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 AND "status" != 0', $sql);
    }

    public function test_where__or(): void
    {
        $sql = $this->selectBuilder()->from('User')->where(fn(ConditionBuilder $q) => $q('id', 1)->or('status', 0))->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE ("id" = 1 OR "status" = 0)', $sql);
    }

    public function test_where__and_plus_or(): void
    {
        $sql = $this->selectBuilder()->from('User')
            ->where('id', 1)
            ->where(fn(ConditionBuilder $q) => $q('status', 0)->or('name', 'John'))
            ->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 AND ("status" = 0 OR "name" = \'John\')', $sql);
    }

    public function test_where__and__with_nested_or(): void
    {
        $sql = $this->selectBuilder()->from('User')
            ->where(static fn(ConditionBuilder $q) => $q->or('status', 1)->or('status', 2))
            ->where('id', 1)
            ->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE ("status" = 1 OR "status" = 2) AND "id" = 1', $sql);
    }

    public function test_orderBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderBy('id')->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 ORDER BY "id"', $sql);
    }

    public function test_orderByAsc(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByAsc('id')->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 ORDER BY "id"', $sql);
    }

    public function test_orderByDesc(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 ORDER BY "id" DESC', $sql);
    }

    public function test_groupBy(): void
    {
        $sql = $this->selectBuilder()->from('User')->groupBy('status')->toSql();
        $this->assertSame('SELECT * FROM "User" GROUP BY "status"', $sql);
    }

    public function test_reorder(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->orderByDesc('id')->reorder()->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $sql);
    }

    public function test_where_and_limit(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 LIMIT 1', $sql);
    }

    public function test_where_and_offset(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->limit(1)->offset(10)->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 LIMIT 1 OFFSET 10', $sql);
    }

    public function test_combination(): void
    {
        $sql = $this->selectBuilder()->from('User')->where('id', 1)->groupBy('status')->having('status', 1)->limit(2)->orderBy('id')->toSql();
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 GROUP BY "status" HAVING "status" = 1 ORDER BY "id" LIMIT 2', $sql);
    }

    public function test_compound_union(): void
    {
        $query = $this->selectBuilder()->from('User_A')->union($this->selectBuilder()->from('User_B'));
        $this->assertSame('(SELECT * FROM "User_A") UNION (SELECT * FROM "User_B")', $query->toSql());
    }

    public function test_compound_union_all(): void
    {
        $query = $this->selectBuilder()->from('User_A')->unionAll($this->selectBuilder()->from('User_B'));
        $this->assertSame('(SELECT * FROM "User_A") UNION ALL (SELECT * FROM "User_B")', $query->toSql());
    }

    public function test_compound_intersect(): void
    {
        $query = $this->selectBuilder()->from('User_A')->intersect($this->selectBuilder()->from('User_B'));
        $this->assertSame('(SELECT * FROM "User_A") INTERSECT (SELECT * FROM "User_B")', $query->toSql());
    }

    public function test_compound_except(): void
    {
        $query = $this->selectBuilder()->from('User_A')->except($this->selectBuilder()->from('User_B'));
        $this->assertSame('(SELECT * FROM "User_A") EXCEPT (SELECT * FROM "User_B")', $query->toSql());
    }

    public function test_cursor(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $conn->query()->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $ptr = $conn->query()->select()->from('User')->cursor();
        $this->assertTrue($ptr->isLazy());
        $this->assertSame([['id' => 1], ['id' => 2]], $ptr->map(fn($row) => (array)$row)->all());
    }

    public function test_exactly__matches(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $conn->query()->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $result = $conn->query()->select()->from('User')->exactly(2);
        $this->assertInstanceOf(QueryResult::class, $result);
    }

    public function test_exactly__does_not_match(): void
    {
        $this->expectException(CountMismatchException::class);
        $this->expectExceptionMessage('Expected count: 3, Got: 2.');

        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $conn->query()->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $conn->query()->select()->from('User')->exactly(3);
    }

    public function test_offsetPaginate(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $conn->query()->insertInto('User')->values([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5]])->execute();
        $query = $conn->query()->select()->from('User');
        $result = $query->offsetPaginate(2, 2);
        $this->assertInstanceOf(OffsetPaginator::class, $result);
        $this->assertSame([['id' => 3], ['id' => 4]], $result->map(fn($row) => (array)$row)->all());
        $this->assertSame(5, $result->totalRows);
        $this->assertSame(3, $result->totalPages);
    }

    public function test_offsetPaginate__with_invalid_page(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid page number. Expected: > 0. Got: 0.');
        $this->selectBuilder()->from('User')->offsetPaginate(0, 2);
    }

    public function test_offsetPaginate__with_invalid_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid page size. Expected: > 0. Got: -1.');
        $this->selectBuilder()->from('User')->offsetPaginate(2, -1);
    }

    public function test_cursorPaginate(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2], ['id' => 3]])->execute();
        $paginator = $query->select()->from('User')->orderBy('id')->cursorPaginate(2);
        $this->assertInstanceOf(CursorPaginator::class, $paginator);
        $this->assertSame(['id' => 2], $paginator->generateNextCursorOrNull()?->parameters);
    }

    public function test_cursorPaginate__with__invalid_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid page size. Expected: > 0. Got: -1.');
        $this->selectBuilder()->from('User')->cursorPaginate(-1);
    }

    public function test_first(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $row = $query->select()->from('User')->first();
        $this->assertSame(['id' => 1], (array) $row);
    }

    public function test_firstOrNull(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();

        $row = $query->select()->from('User')->firstOrNull();
        $this->assertNull($row);

        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $row = $query->select()->from('User')->first();
        $this->assertSame(['id' => 1], (array) $row);
    }

    public function test_single(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1]])->execute();
        $row = $query->select()->from('User')->single();
        $this->assertSame(['id' => 1], (array) $row);
    }

    public function test_single__empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');

        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->select()->from('User')->single();
    }

    public function test_single__multiple_rows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');

        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $query->select()->from('User')->single();
    }

    public function test_pluck(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $result = $query->select()->from('User')->pluck('id');
        $this->assertSame([1, 2], $result->all());
    }

    public function test_value(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $value = $query->select()->from('User')->orderByDesc('id')->value('id');
        $this->assertSame(2, $value);
    }

    public function test_value__empty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected query to return a row, but none was returned.');

        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->select()->from('User')->orderByDesc('id')->value('id');
    }

    public function test_value__unknown_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'x' does not exist.");

        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1]])->execute();
        $query->select()->from('User')->orderByDesc('id')->value('x');
    }

    public function test_valueOrNull(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $value = $query->select()->from('User')->orderByDesc('id')->valueOrNull('id');
        $this->assertSame(2, $value);
    }

    public function test_valueOrNull__empty(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $value = $query->select()->from('User')->orderByDesc('id')->valueOrNull('id');
        $this->assertNull($value);
    }

    public function test_valueOrNull__unknown_column(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1]])->execute();
        $value = $query->select()->from('User')->orderByDesc('id')->valueOrNull('x');
        $this->assertNull($value);
    }

    public function test_exists__returns_true(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1]])->execute();
        $exists = $query->select()->from('User')->exists();
        $this->assertTrue($exists);
    }

    public function test_exists__returns_false(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $exists = $query->select()->from('User')->exists();
        $this->assertFalse($exists);
    }

    public function test_count__nothing(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $count = $query->select()->from('User')->count();
        $this->assertSame(0, $count);
    }

    public function test_count__some(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $count = $query->select()->from('User')->count();
        $this->assertSame(2, $count);
    }

    public function test_count__with_groupBy_throws_error(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get count when GROUP BY is defined. Use tally instead.');

        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->string('s');
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([
            ['id' => 1, 's' => 'a'],
            ['id' => 2, 's' => 'a'],
            ['id' => 3, 's' => 'b'],
        ])->execute();
        $query->select()->from('User')->groupBy('name')->count();
    }

    public function test_tally(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->string('s');
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([
            ['id' => 1, 's' => 'a'],
            ['id' => 2, 's' => 'a'],
            ['id' => 3, 's' => 'b'],
        ])->execute();
        $tally = $query->select()->from('User')->groupBy('s')->tally();
        $this->assertSame(['a' => 2, 'b' => 1], $tally);
    }

    public function test_tally__without_grouping(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot tally without a GROUP BY clause. Use count instead.');

        $conn = $this->connect();
        $conn->query()->select()->from('User')->tally();
    }

    public function test_sum(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2]])->execute();
        $sum = $query->select()->from('User')->sum('id');
        $this->assertSame(3, $sum);
    }

    public function test_avg(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2], ['id' => 3]])->execute();
        $avg = $query->select()->from('User')->avg('id');
        $this->assertSame(2.0, $avg);
    }

    public function test_min(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2], ['id' => 3]])->execute();
        $min = $query->select()->from('User')->min('id');
        $this->assertSame(1, $min);
    }

    public function test_max(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2], ['id' => 3]])->execute();
        $max = $query->select()->from('User')->max('id');
        $this->assertSame(3, $max);
    }

    public function test_batch__without_limit(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2], ['id' => 3]])->execute();
        $batch = $query->select()->from('User')->orderBy('id')->batch(2);
        $batches = iterator_to_array($batch);
        $this->assertCount(2, $batches);
        $this->assertInstanceOf(CursorPaginator::class, $batches[0]);
        $this->assertInstanceOf(CursorPaginator::class, $batches[1]);
        $this->assertSame([['id' => 1], ['id' => 2]], $batches[0]->map(fn($r) => (array)$r)->all());
        $this->assertSame([['id' => 3]], $batches[1]->map(fn($r) => (array)$r)->all());
    }

    public function test_batch__with_limit_less_than_size(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->values([['id' => 1], ['id' => 2], ['id' => 3]])->execute();

        $this->captureEvents(QueryExecuted::class);

        $batch = $query->select()->from('User')->orderBy('id')->limit(2)->batch(3);
        $batches = iterator_to_array($batch);
        $this->assertCount(1, $batches);
        $this->assertInstanceOf(CursorPaginator::class, $batches[0]);
        $this->assertSame([['id' => 1], ['id' => 2]], $batches[0]->map(fn($r) => (array) $r)->all());
        $this->assertCount(1, $this->capturedEvents);
    }

    public function test_batch__with_limit_greater_than_size(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $dataset = array_map(fn($i) => ['id' => $i], range(1, 7));
        $query->insertInto('User')->values($dataset)->execute();

        $this->captureEvents(QueryExecuted::class);

        $batch = $query->select()->from('User')->orderBy('id')->limit(5)->batch(2);
        $batches = iterator_to_array($batch);
        $this->assertCount(3, $batches);
        foreach ($batches as $batch) {
            $this->assertInstanceOf(CursorPaginator::class, $batch);
        }
        $this->assertSame([['id' => 1], ['id' => 2]], $batches[0]->map(fn($r) => (array) $r)->all());
        $this->assertSame([['id' => 3], ['id' => 4]], $batches[1]->map(fn($r) => (array) $r)->all());
        $this->assertSame([['id' => 5]], $batches[2]->map(fn($r) => (array) $r)->all());
        $this->assertCount(3, $this->capturedEvents);
    }

    public function test_flatBatch(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query();
        $dataset = array_map(fn($i) => ['id' => $i], range(1, 7));
        $query->insertInto('User')->values($dataset)->execute();

        $this->captureEvents(QueryExecuted::class);

        $generator = $query->select()->from('User')->orderBy('id')->limit(5)->flatBatch(2);
        $results = iterator_to_array($generator);
        $this->assertCount(5, $results);
        foreach ($results as $row) {
            $this->assertInstanceOf(stdClass::class, $row);
        }
        $capturedEvents = $this->getCapturedEvents(QueryExecuted::class);
        $this->assertSame('SELECT * FROM "User" ORDER BY "id" LIMIT 3', $capturedEvents[0]->result->template);
        $this->assertSame('SELECT * FROM "User" WHERE ("id") > (?) ORDER BY "id" LIMIT 3', $capturedEvents[1]->result->template);
        $this->assertCount(3, $capturedEvents);
    }

    public function test_compound_orderBy(): void
    {
        $query = $this->selectBuilder()->from('User_A')
            ->union($this->selectBuilder()->from('User_B'))
            ->orderBy('id');
        $this->assertSame('(SELECT * FROM "User_A") UNION (SELECT * FROM "User_B") ORDER BY "id"', $query->toSql());
    }

    public function test_compound_orderByAsc(): void
    {
        $query = $this->selectBuilder()->from('User_A')
            ->union($this->selectBuilder()->from('User_B'))
            ->orderByAsc('id');
        $this->assertSame('(SELECT * FROM "User_A") UNION (SELECT * FROM "User_B") ORDER BY "id"', $query->toSql());
    }

    public function test_compound_orderByDesc(): void
    {
        $query = $this->selectBuilder()->from('User_A')
            ->union($this->selectBuilder()->from('User_B'))
            ->orderByDesc('id');
        $this->assertSame('(SELECT * FROM "User_A") UNION (SELECT * FROM "User_B") ORDER BY "id" DESC', $query->toSql());
    }

    public function test_compound_reorder(): void
    {
        $query = $this->selectBuilder()->from('User_A')
            ->union($this->selectBuilder()->from('User_B'))
            ->orderByDesc('id')
            ->reorder();
        $this->assertSame('(SELECT * FROM "User_A") UNION (SELECT * FROM "User_B")', $query->toSql());
    }

    public function test_compound_limit(): void
    {
        $query = $this->selectBuilder()->from('User_A')->union($this->selectBuilder()->from('User_B'))->limit(1);
        $this->assertSame('(SELECT * FROM "User_A") UNION (SELECT * FROM "User_B") LIMIT 1', $query->toSql());
    }

    public function test___clone(): void
    {
        $base = $this->selectBuilder()->from('User')->where('id', 1);
        $copy = clone $base;
        $base->where('id', in: [3, 4]); // change $base but should not be reflected on copy
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1 AND "id" IN (3, 4)', $base->toSql());
        $this->assertSame('SELECT * FROM "User" WHERE "id" = 1', $copy->toSql());
        $this->assertNotSame($base->toSql(), $copy->toSql());
    }

    abstract public function test_explain(): void;

    public function test___clone__on_bare(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query()->select()->from('User AS u');
        $copy = clone $query;

        $this->assertInstanceOf(SelectBuilder::class, $copy);
        $this->assertSame($query->toSql(), $copy->toSql());
        $this->assertNotSame($query->statement, $copy->statement);
    }

    public function test___clone__with_where(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->execute();

        $query = $conn->query()
            ->select()->from('User AS u')
            ->where(fn(ConditionBuilder $q) => $q->or('u.id', 1)->and('u.id', 2))
            ->orderBy('u.id');
        $copy = clone $query;

        $this->assertInstanceOf(SelectBuilder::class, $copy);
        $this->assertSame($query->toSql(), $copy->toSql());
        $this->assertNotSame($query->statement, $copy->statement);
        $this->assertNotSame($query->statement->where, $copy->statement->where);
        $this->assertNotSame($query->statement->where?->value, $copy->statement->where?->value);
        $this->assertSame($query->statement->orderBy, $copy->statement->orderBy);
    }

    public function test_getStatement(): void
    {
        $query = $this->selectBuilder()->from('User')->where('id', 1);
        $this->assertInstanceOf(SelectStatement::class, $query->statement);
    }

    public function test_execute(): void
    {
        $conn = $this->createTempConnection($this->useConnection);
        $table = $conn->schema()->createTable('t');
        $table->id();
        $table->execute();
        $result = $conn->query()->select()->from('t')->where('id', 1)->execute();
        $this->assertSame([], $result->all());
    }
}
