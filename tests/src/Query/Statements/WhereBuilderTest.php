<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Exceptions\InvalidArgumentException;
use Kirameki\Exceptions\LogicException;
use Kirameki\Exceptions\NotSupportedException;
use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\CheckingCondition;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\NestedCondition;
use Kirameki\Database\Query\Statements\RawCondition;
use Kirameki\Database\Query\Statements\Tuple;
use Kirameki\Database\Query\Statements\ComparingCondition;
use Kirameki\Database\Query\Statements\Logic;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Raw;
use Tests\Kirameki\Database\Query\QueryTestCase;
use ValueError;

class WhereBuilderTest extends QueryTestCase
{
    protected string $useConnection = 'mysql';

    public function test_where__with_zero_args(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of arguments. Expected: <= 3. Got: 0');
        $this->connect()->query()->select()->from('t')->where();
    }

    public function test_where__with_one_arg__itself(): void
    {
        $query = $this->connect()->query();
        $select = $query->select()->from('t');
        $select->where('id', 1);

        $it = $query->select()->where($select->statement->where);
        $this->assertNotSame($select, $it);

        $def = $it->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertNull($def->logic);
        $this->assertNull($def->next);
    }

    public function test_where__with_1_arg__closure(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $query->where('id', 1);
        $query->where(fn(ConditionBuilder $q) => $q->where('id', 2)->or('id', 3));
        $query->where('id', 4);

        $def = $query->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertSame(Logic::And, $def->logic);
        $this->assertInstanceOf(NestedCondition::class, $def->next);

        $def = $def->next;
        $cond = $def->value;
        $this->assertInstanceOf(NestedCondition::class, $def);
        $this->assertInstanceOf(ComparingCondition::class, $cond);
        $this->assertSame('id', $cond->column);
        $this->assertSame(2, $cond->value);
        $this->assertSame(Operator::Equals, $cond->operator);
        $this->assertSame(Logic::Or, $cond->logic);
        $this->assertInstanceOf(ComparingCondition::class, $def->next);

        $cond = $cond->next;
        $this->assertInstanceOf(ComparingCondition::class, $cond);
        $this->assertSame('id', $cond->column);
        $this->assertSame(3, $cond->value);
        $this->assertSame(Operator::Equals, $cond->operator);
        $this->assertNull($cond->logic);
        $this->assertNull($cond->next);

        $def = $def->next;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(4, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertNull($def->logic);
        $this->assertNull($def->next);
    }

    public function test_where__with_one_arg__invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: Condition|ConditionBuilder|Closure. Got: string.');
        $query = $this->connect()->query()->select()->from('t');
        $query->where('id');
    }

    public function test_where__with_two_args__named_column(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where(lt: 1, column: 'id')->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
    }

    public function test_where__with_two_args__no_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing column parameter.');
        $query = $this->connect()->query()->select()->from('t');
        $query->where(lt: 1, not: 1);
    }

    public function test_where__with_two_args__positional_scalar(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
    }

    public function test_where__with_two_args__positional_iterable(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', [1, 2, 3])->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::In, $def->operator);
    }

    public function test_where__with_two_args__positional_range(): void
    {
        $bounds = Bounds::excluded(1, 10);
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', $bounds)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame($bounds, $def->value);
        $this->assertSame(Operator::InRange, $def->operator);
    }

    public function test_where__with_two_args__not_with_scalar(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', not: 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::NotEquals, $def->operator);
    }

    public function test_where__with_two_args__not_with_iterable(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', not: [1, 2, 3])->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::NotIn, $def->operator);
    }

    public function test_where__with_two_args__not_with_range(): void
    {
        $bounds = Bounds::excluded(1, 10);
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', not: $bounds)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame($bounds, $def->value);
        $this->assertSame(Operator::NotInRange, $def->operator);
    }

    public function test_where__with_two_args__lt_with_scalar(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', lt: 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
    }

    public function test_where__with_two_args__lte_with_scalar(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', lte: 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThanOrEqualTo, $def->operator);
    }

    public function test_where__with_two_args__gt_with_scalar(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', gt: 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::GreaterThan, $def->operator);
    }

    public function test_where__with_two_args__gte_with_scalar(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', gte: 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::GreaterThanOrEqualTo, $def->operator);
    }

    public function test_where__with_two_args__in_with_iterable(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', in: [1, 2, 3])->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::In, $def->operator);
    }

    public function test_where__with_two_args__not_in_with_iterable(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', notIn: [1, 2, 3])->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::NotIn, $def->operator);
    }

    public function test_where__with_two_args__between(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', between: [1, 10])->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 10], $def->value);
        $this->assertSame(Operator::Between, $def->operator);
    }

    public function test_where__with_two_args__not_between(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', notBetween: [1, 10])->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 10], $def->value);
        $this->assertSame(Operator::NotBetween, $def->operator);
    }

    public function test_where__with_two_args__like(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('name', like: 'John%')->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('name', $def->column);
        $this->assertSame('John%', $def->value);
        $this->assertSame(Operator::Like, $def->operator);
    }

    public function test_where__with_two_args__not_like(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('name', notLike: 'John%')->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('name', $def->column);
        $this->assertSame('John%', $def->value);
        $this->assertSame(Operator::NotLike, $def->operator);
    }

    public function test_where__with_two_args__unknown_operator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown operator: "t".');
        $this->connect()->query()->select()->from('t')->where('id', t: 1);
    }

    public function test_where__with_three_args(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', Operator::NotEquals, 2)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(2, $def->value);
        $this->assertSame(Operator::NotEquals, $def->operator);
        $this->assertNull($def->logic);
        $this->assertNull($def->next);
    }

    public function test_where__with_three_args__invalid_operator(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('"T" is not a valid backing value for enum ' . Operator::class);
        $this->connect()->query()->select()->from('t')->where('id', 't', 1);
    }

    public function test_where__with_expression_column(): void
    {
        $expr = new Raw('id');
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where($expr, 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame($expr, $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
    }

    public function test_where__with_tuple_column(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where(new Tuple('a', 'b'), Operator::LessThan, 1)->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertInstanceOf(Tuple::class, $def->column);
        $this->assertSame(['a', 'b'], $def->column->items);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
    }

    public function test_where__compound__with_another_where(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->where('id', 1)->where('name', not: 'a')->statement->where;
        $this->assertInstanceOf(ComparingCondition::class, $def);
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertSame(Logic::And, $def->logic);
        $this->assertInstanceOf(ComparingCondition::class, $def->next);
        $this->assertSame('name', $def->next->column);
        $this->assertSame('a', $def->next->value);
        $this->assertSame(Operator::NotEquals, $def->next->operator);
        $this->assertNull($def->next->logic);
    }

    public function test_whereRaw(): void
    {
        $query = $this->connect()->query()->select()->from('t');
        $def = $query->whereRaw('id = 1')->statement->where;
        $this->assertInstanceOf(RawCondition::class, $def);
        $this->assertInstanceOf(Raw::class, $def->value);
        $this->assertSame('id = 1', $def->value->value);
    }

    public function test_whereExists(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $q = $conn->query()->select()->from('t')->whereExists($subquery);
        $def = $q->statement->where;
        $this->assertInstanceOf(CheckingCondition::class, $def);
        $this->assertNotSame($subquery->statement, $def->value);
        $this->assertSame('SELECT * FROM "t" WHERE EXISTS (SELECT "id" FROM "t2")', $q->toSql());
    }

    public function test_whereNotExists(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $q = $conn->query()->select()->from('t')->whereNotExists($subquery);
        $def = $q->statement->where;
        $this->assertInstanceOf(CheckingCondition::class, $def);
        $this->assertNotSame($subquery->statement, $def->value);
        $this->assertSame('SELECT * FROM "t" WHERE NOT EXISTS (SELECT "id" FROM "t2")', $q->toSql());
    }

    public function test___clone(): void
    {
        $conn = $this->sqliteConnection();
        $query = $conn->query()->select('id')->from('t2')->where('id', 1);
        $cloned = clone $query;
        $next = $query->where('id', 2);
        $this->assertSame($query, $next);
        $this->assertSame($query->statement, $next->statement);
        $this->assertNotSame($query, $cloned);
        $this->assertNotSame($query->statement, $cloned->statement);
        $this->assertNotSame($next, $cloned);
        $this->assertNotSame($next->statement, $cloned->statement);
    }

    public function test_where__equals__with_scalar_value(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', true);
        $this->assertSame('SELECT * FROM "t" WHERE "b" = TRUE', $q->toSql());
    }

    public function test_where__equals__with_tuple(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where(new Tuple('a', 'b'), eq: new Tuple(1, 2));
        $this->assertSame('SELECT * FROM "t" WHERE ("a", "b") = (1, 2)', $q->toSql());
    }

    public function test_where__notEquals__with_scalar_value(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', ne: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "b" != 1', $q->toSql());
    }

    public function test_where__notEquals__with_tuple(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where(new Tuple('a', 'b'), ne: new Tuple(1, 2));
        $this->assertSame('SELECT * FROM "t" WHERE ("a", "b") != (1, 2)', $q->toSql());
    }

    public function test_greaterThanOrEqualTo__with_scalar_value(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', gte: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "b" >= 1', $q->toSql());
    }

    public function test_greaterThanOrEqualTo__with_tuple(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where(new Tuple('a', 'b'), gte: new Tuple(1, 2));
        $this->assertSame('SELECT * FROM "t" WHERE ("a", "b") >= (1, 2)', $q->toSql());
    }

    public function test_greaterThan__with_scalar_value(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', gt: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "b" > 1', $q->toSql());
    }

    public function test_greaterThan__with_tuple(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where(new Tuple('a', 'b'), gt: new Tuple(1, 2));
        $this->assertSame('SELECT * FROM "t" WHERE ("a", "b") > (1, 2)', $q->toSql());
    }

    public function test_lessThanOrEqualTo__with_scalar_value(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', lte: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "b" <= 1', $q->toSql());
    }

    public function test_lessThanOrEqualTo__with_tuple(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where(new Tuple('a', 'b'), lte: new Tuple(1, 2));
        $this->assertSame('SELECT * FROM "t" WHERE ("a", "b") <= (1, 2)', $q->toSql());
    }

    public function test_lessThan(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', lt: 1);
        $this->assertSame('SELECT * FROM "t" WHERE "b" < 1', $q->toSql());
    }

    public function test_isNull(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', null);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IS NULL', $q->toSql());
    }

    public function test_isNotNull(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', not: null);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IS NOT NULL', $q->toSql());
    }

    public function test_like(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('name', like: 'John%');
        $this->assertSame('SELECT * FROM "t" WHERE "name" LIKE \'John%\'', $q->toSql());
    }

    public function test_notLike(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('name', notLike: 'John%');
        $this->assertSame('SELECT * FROM "t" WHERE "name" NOT LIKE \'John%\'', $q->toSql());
    }

    public function test_in__with_null(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Value for WHERE IN. Expected: iterable|SelectStatement. Got: null.');
        $conn = $this->sqliteConnection();
        $conn->query()->select()->from('t')->where('b', in: null)->toSql();
    }

    public function test_in__with_array(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', in: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IN (1, 2)', $q->toSql());
    }

    public function test_in__with_array_containing_null(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', in: [1, null]);
        $this->assertSame('SELECT * FROM "t" WHERE ("b" IN (1) OR "b" IS NULL)', $q->toSql());
    }

    public function test_in__with_empty_array(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', in: []);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IN (NULL)', $q->toSql());
    }

    public function test_in__with_subquery(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $q = $conn->query()->select()->from('t')->where('b', in: $subquery);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IN (SELECT "id" FROM "t2")', $q->toSql());
    }

    public function test_notIn__with_array(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', notIn: [1, 2]);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT IN (1, 2)', $q->toSql());
    }

    public function test_notIn__with_subquery(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $q = $conn->query()->select()->from('t')->where('b', notIn: $subquery);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT IN (SELECT "id" FROM "t2")', $q->toSql());
    }

    public function test_notIn__with_empty_array(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', notIn: []);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT IN (NULL)', $q->toSql());
    }

    public function test_between(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', between: [1, 10]);
        $this->assertSame('SELECT * FROM "t" WHERE "b" BETWEEN 1 AND 10', $q->toSql());
    }

    public function test_between__with_less_than_2_values_in_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: 2 values for BETWEEN condition. Got: 1.');
        $conn = $this->sqliteConnection();
        $conn->query()->select()->from('t')->where('b', between: [1])->toSql();
    }

    public function test_between__with_more_than_2_values_in_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: 2 values for BETWEEN condition. Got: 3.');
        $conn = $this->sqliteConnection();
        $conn->query()->select()->from('t')->where('b', between: [1, 2, 3])->toSql();
    }

    public function test_notBetween(): void
    {
        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where('b', notBetween: [1, 10]);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT BETWEEN 1 AND 10', $q->toSql());
    }

    public function test_notBetween__with_less_than_2_values_in_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: 2 values for BETWEEN condition. Got: 1.');
        $conn = $this->sqliteConnection();
        $conn->query()->select()->from('t')->where('b', notBetween: [1])->toSql();
    }

    public function test_notBetween__with_more_than_2_values_in_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: 2 values for BETWEEN condition. Got: 3.');
        $conn = $this->sqliteConnection();
        $conn->query()->select()->from('t')->where('b', notBetween: [1, 2, 3])->toSql();
    }

    public function test_inRange(): void
    {
        $conn = $this->sqliteConnection();
        $bounds = Bounds::excluded(1, 10);
        $q = $conn->query()->select()->from('t')->where('b', $bounds);
        $this->assertSame('SELECT * FROM "t" WHERE "b" > 1 AND "b" < 10', $q->toSql());
    }

    public function test_notInRange(): void
    {
        $conn = $this->sqliteConnection();
        $bounds = Bounds::excluded(1, 10);
        $q = $conn->query()->select()->from('t')->where('b', not: $bounds);
        $this->assertSame('SELECT * FROM "t" WHERE "b" <= 1 OR "b" >= 10', $q->toSql());
    }
}
