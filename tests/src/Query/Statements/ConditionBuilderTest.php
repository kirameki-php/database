<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Functions\CurrentTimestamp;
use Kirameki\Database\Query\Expressions\Column;
use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Statements\Logic;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Raw;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function dump;

class ConditionBuilderTest extends QueryTestCase
{
    protected string $useConnection = 'mysql';

    public function test_fromArgs__with_zero_args(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid number of arguments. Expected: <= 2. Got: 0');
        ConditionBuilder::fromArgs();
    }

    public function test_fromArgs__with_one_arg__itself(): void
    {
        $self = ConditionBuilder::fromArgs('id', 1);
        $builder = ConditionBuilder::fromArgs($self);
        $this->assertSame($self, $builder);

        $def = $builder->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);
        $this->assertNull($def->nextLogic);
        $this->assertNull($def->next);
    }

    public function test_fromArgs__with_one_arg__invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: Kirameki\Database\Query\Statements\ConditionBuilder. Got: string.');
        ConditionBuilder::fromArgs('id');
    }

    public function test_fromArgs__with_two_arg__named_column(): void
    {
        $def = ConditionBuilder::fromArgs(lt: 1, column: 'id')->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__with_two_arg__no_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing column parameter.');
        $def = ConditionBuilder::fromArgs(lt: 1, a: 'id')->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__positional_scalar(): void
    {
        $def = ConditionBuilder::fromArgs('id', 1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__positional_iterable(): void
    {
        $def = ConditionBuilder::fromArgs('id', [1, 2, 3])->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__positional_range(): void
    {
        $bounds = Bounds::excluded(1, 10);
        $def = ConditionBuilder::fromArgs('id', $bounds)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame($bounds, $def->value);
        $this->assertSame(Operator::Range, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__not_with_scalar(): void
    {
        $def = ConditionBuilder::fromArgs('id', not: 1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertTrue($def->negated);
    }

    public function test_fromArgs__not_with_iterable(): void
    {
        $def = ConditionBuilder::fromArgs('id', not: [1, 2, 3])->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertTrue($def->negated);
    }

    public function test_fromArgs__not_with_range(): void
    {
        $bounds = Bounds::excluded(1, 10);
        $def = ConditionBuilder::fromArgs('id', not: $bounds)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame($bounds, $def->value);
        $this->assertSame(Operator::Range, $def->operator);
        $this->assertTrue($def->negated);
    }

    public function test_fromArgs__lt_with_scalar(): void
    {
        $def = ConditionBuilder::fromArgs('id', lt: 1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__lte_with_scalar(): void
    {
        $def = ConditionBuilder::fromArgs('id', lte: 1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThanOrEqualTo, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__gt_with_scalar(): void
    {
        $def = ConditionBuilder::fromArgs('id', gt: 1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::GreaterThan, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__gte_with_scalar(): void
    {
        $def = ConditionBuilder::fromArgs('id', gte: 1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::GreaterThanOrEqualTo, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__in_with_iterable(): void
    {
        $def = ConditionBuilder::fromArgs('id', in: [1, 2, 3])->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__not_in_with_iterable(): void
    {
        $def = ConditionBuilder::fromArgs('id', notIn: [1, 2, 3])->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 2, 3], $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertTrue($def->negated);
    }

    public function test_fromArgs__between(): void
    {
        $def = ConditionBuilder::fromArgs('id', between: [1, 10])->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 10], $def->value);
        $this->assertSame(Operator::Between, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__not_between(): void
    {
        $def = ConditionBuilder::fromArgs('id', notBetween: [1, 10])->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame([1, 10], $def->value);
        $this->assertSame(Operator::Between, $def->operator);
        $this->assertTrue($def->negated);
    }

    public function test_fromArgs__like(): void
    {
        $def = ConditionBuilder::fromArgs('name', like: 'John%')->getDefinition();
        $this->assertSame('name', $def->column);
        $this->assertSame('John%', $def->value);
        $this->assertSame(Operator::Like, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_fromArgs__not_like(): void
    {
        $def = ConditionBuilder::fromArgs('name', notLike: 'John%')->getDefinition();
        $this->assertSame('name', $def->column);
        $this->assertSame('John%', $def->value);
        $this->assertSame(Operator::Like, $def->operator);
        $this->assertTrue($def->negated);
    }

    public function test_fromArgs__unknown_operator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown operator: "t".');
        ConditionBuilder::fromArgs('id', t: 1);
    }

    public function test_for__with_string(): void
    {
        $def = ConditionBuilder::for('id')->equals(1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_for__with_iterable(): void
    {
        $def = ConditionBuilder::for(['a', 'b'])->equals(1)->getDefinition();
        $this->assertSame(['a', 'b'], $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_for__with_expression(): void
    {
        $expr = new Raw('id');
        $def = ConditionBuilder::for($expr)->equals(1)->getDefinition();
        $this->assertSame($expr, $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_with__with_string_column(): void
    {
        $def = ConditionBuilder::with('id', Operator::LessThan, 1)->getDefinition();
        $this->assertSame('id', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_with__with_iterable_column(): void
    {
        $def = ConditionBuilder::with(['a', 'b'], Operator::LessThan, 1)->getDefinition();
        $this->assertSame(['a', 'b'], $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_with__with_expression_column(): void
    {
        $expr = new Raw('id');
        $def = ConditionBuilder::with($expr, Operator::LessThan, 1)->getDefinition();
        $this->assertSame($expr, $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_raw__with_string(): void
    {
        $def = ConditionBuilder::raw('id = 1')->getDefinition();
        $this->assertSame('_UNUSED_', $def->column);
        $this->assertInstanceOf(Raw::class, $def->value);
        $this->assertSame(Operator::Raw, $def->operator);
        $this->assertFalse($def->negated);
    }

    public function test_raw__with_expression(): void
    {
        $expr = new Raw('id = 1');
        $cond = ConditionBuilder::raw($expr);
        $def = $cond->getDefinition();
        $this->assertSame('_UNUSED_', $def->column);
        $this->assertSame($expr, $def->value);
        $this->assertSame(Operator::Raw, $def->operator);
        $this->assertFalse($def->negated);

        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE id = 1', $q->toString());
    }

    public function test_exists(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $cond = ConditionBuilder::exists($subquery);
        $def = $cond->getDefinition();
        $this->assertSame('_UNUSED_', $def->column);
        $this->assertSame($subquery->getStatement(), $def->value);
        $this->assertSame(Operator::Exists, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE EXISTS (SELECT "id" FROM "t2")', $q->toString());
    }

    public function test_notExists(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $cond = ConditionBuilder::notExists($subquery);
        $def = $cond->getDefinition();
        $this->assertSame('_UNUSED_', $def->column);
        $this->assertSame($subquery->getStatement(), $def->value);
        $this->assertSame(Operator::Exists, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE NOT EXISTS (SELECT "id" FROM "t2")', $q->toString());
    }

    public function test___clone(): void
    {
        $current = ConditionBuilder::for('id')->equals(1);
        $next = $current->or()->equals(2);
        $cloned = clone $current;
        $this->assertNotSame($current, $cloned);
        $this->assertNotSame($next, $cloned);
        $this->assertNotSame($current->getDefinition(), $cloned->getDefinition());
        $this->assertNotSame($next->getDefinition(), $cloned->getDefinition());
        $this->assertSame($current->getDefinition()->column, $cloned->getDefinition()->column);
        $this->assertSame($current->getDefinition()->value, $cloned->getDefinition()->value);
        $this->assertSame($current->getDefinition()->operator, $cloned->getDefinition()->operator);
        $this->assertSame($current->getDefinition()->negated, $cloned->getDefinition()->negated);
        $this->assertSame(2, $cloned->getDefinition()->next?->value);
        $this->assertSame($current->getDefinition()->column, $cloned->getDefinition()->next->column);
        $this->assertNull($cloned->getDefinition()->next->next);
    }

    public function test_and__without_column(): void
    {
        $conn = $this->sqliteConnection();
        $current = ConditionBuilder::for('id')->equals(1);
        $def = $current->getDefinition();
        $next = $current->and()->equals(2);
        $this->assertSame($current, $next);

        $next = $def->next;
        $this->assertNotNull($next);
        $this->assertSame('id', $next->column);
        $this->assertSame(2, $next->value);
        $this->assertSame(Operator::Equals, $next->operator);
        $this->assertFalse($next->negated);
        $this->assertSame(Logic::And, $def->nextLogic);

        $q = $conn->query()->select()->from('t')->where($current);
        $this->assertSame('SELECT * FROM "t" WHERE ("id" = 1 AND "id" = 2)', $q->toString());
    }

    public function test_and__with_column(): void
    {
        $conn = $this->sqliteConnection();
        $current = ConditionBuilder::for('id')->equals(1);
        $next = $current->and('name')->equals('a');
        $this->assertSame($current, $next);

        $q = $conn->query()->select()->from('t')->where($current);
        $this->assertSame('SELECT * FROM "t" WHERE ("id" = 1 AND "name" = \'a\')', $q->toString());
    }

    public function test_or__without_column(): void
    {
        $conn = $this->sqliteConnection();
        $current = ConditionBuilder::for('id')->equals(1);
        $def = $current->getDefinition();
        $next = $current->or()->equals(2);
        $this->assertSame($current, $next);

        $next = $def->next;
        $this->assertNotNull($next);
        $this->assertSame('id', $next->column);
        $this->assertSame(2, $next->value);
        $this->assertSame(Operator::Equals, $next->operator);
        $this->assertFalse($next->negated);
        $this->assertSame(Logic::Or, $def->nextLogic);

        $q = $conn->query()->select()->from('t')->where($current);
        $this->assertSame('SELECT * FROM "t" WHERE ("id" = 1 OR "id" = 2)', $q->toString());
    }

    public function test_and_or_multi_define(): void
    {
        $conn = $this->sqliteConnection();
        $current = ConditionBuilder::for('id')->equals(1)
            ->and()->greaterThan(2)
            ->or()->lessThan(3);

        $q = $conn->query()->select()->from('t')->where($current);
        $this->assertSame('SELECT * FROM "t" WHERE ("id" = 1 AND "id" > 2 OR "id" < 3)', $q->toString());
    }

    public function test_equals__with_scalar(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->equals(true);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertTrue($def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" = TRUE', $q->toString());
    }

    public function test_equals__with_iterable_and_fail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Iterable should use in(iterable $iterable) method.');
        ConditionBuilder::for('b')->equals([1, 2]);
    }

    public function test_notEquals(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->notEquals(true);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertTrue($def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" != TRUE', $q->toString());
    }

    public function test_greaterThanOrEquals(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->greaterThanOrEqualTo(1);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::GreaterThanOrEqualTo, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" >= 1', $q->toString());
    }

    public function test_greaterThan(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->greaterThan(1);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::GreaterThan, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" > 1', $q->toString());
    }

    public function test_lessThanOrEqualTo(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->lessThanOrEqualTo(1);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThanOrEqualTo, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" <= 1', $q->toString());
    }

    public function test_lessThan(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->lessThan(1);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame(1, $def->value);
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" < 1', $q->toString());
    }

    public function test_isNull(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->isNull();
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertNull($def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IS NULL', $q->toString());
    }

    public function test_isNotNull(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->isNotNull();
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertNull($def->value);
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IS NOT NULL', $q->toString());
    }

    public function test_like(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('name')->like('John%');
        $def = $cond->getDefinition();
        $this->assertSame('name', $def->column);
        $this->assertSame('John%', $def->value);
        $this->assertSame(Operator::Like, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "name" LIKE \'John%\'', $q->toString());
    }

    public function test_notLike(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('name')->notLike('John%');
        $def = $cond->getDefinition();
        $this->assertSame('name', $def->column);
        $this->assertSame('John%', $def->value);
        $this->assertSame(Operator::Like, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "name" NOT LIKE \'John%\'', $q->toString());
    }

    public function test_in__with_array(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->in([1, 2]);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame([1, 2], $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IN (1, 2)', $q->toString());
    }

    public function test_in__with_empty_array(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->in([]);
        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE 1 = 0', $q->toString());
    }

    public function test_in__with_subquery(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $cond = ConditionBuilder::for('b')->in($subquery);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame($subquery->getStatement(), $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IN (SELECT "id" FROM "t2")', $q->toString());
    }

    public function test_notIn__with_array(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->notIn([1, 2]);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame([1, 2], $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT IN (1, 2)', $q->toString());
    }

    public function test_notIn__with_subquery(): void
    {
        $conn = $this->sqliteConnection();
        $subquery = $conn->query()->select('id')->from('t2');
        $cond = ConditionBuilder::for('b')->notIn($subquery);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame($subquery->getStatement(), $def->value);
        $this->assertSame(Operator::In, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT IN (SELECT "id" FROM "t2")', $q->toString());
    }

    public function test_notIn__with_empty_array(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->notIn([]);
        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE 1 = 0', $q->toString());
    }

    public function test_between(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->between(1, 10);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame([1, 10], $def->value);
        $this->assertSame(Operator::Between, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" BETWEEN 1 AND 10', $q->toString());
    }

    public function test_notBetween(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->notBetween(1, 10);
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame([1, 10], $def->value);
        $this->assertSame(Operator::Between, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT BETWEEN 1 AND 10', $q->toString());
    }

    public function test_inRange(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->inRange(Bounds::excluded(1, 10));
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertInstanceOf(Bounds::class, $def->value);
        $this->assertSame(Operator::Range, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" > 1 AND "b" < 10', $q->toString());
    }

    public function test_notInRange(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->notInRange(Bounds::excluded(1, 10));
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertInstanceOf(Bounds::class, $def->value);
        $this->assertSame(Operator::Range, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" <= 1 OR "b" >= 10', $q->toString());
    }

    public function test_apply(): void
    {
        $cond = ConditionBuilder::for('a')->equals(1);
        $cond->apply(ConditionBuilder::for('b')->greaterThan(2));
        $def = $cond->getDefinition();
        $this->assertSame('b', $def->column);
        $this->assertSame(2, $def->value);
        $this->assertSame(Operator::GreaterThan, $def->operator);
        $this->assertFalse($def->negated);
        $this->assertSame(null, $def->nextLogic);
        $this->assertSame(null, $def->next);

        $conn = $this->sqliteConnection();
        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" > 2', $q->toString());
    }

    public function test_negate_equals(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->equals(1)->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" != 1', $q->toString());
    }

    public function test_negate_greaterThanOrEquals(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->greaterThanOrEqualTo(1)->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::GreaterThanOrEqualTo, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" < 1', $q->toString());
    }

    public function test_negate_greaterThan(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->greaterThan(1)->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::GreaterThan, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" <= 1', $q->toString());
    }

    public function test_negate_lessThanOrEqualTo(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->lessThanOrEqualTo(1)->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::LessThanOrEqualTo, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" > 1', $q->toString());
    }

    public function test_negate_lessThan(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->lessThan(1)->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::LessThan, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" >= 1', $q->toString());
    }

    public function test_negate_isNull(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->isNull()->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IS NOT NULL', $q->toString());
    }

    public function test_negate_isNotNull(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->isNotNull()->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::Equals, $def->operator);
        $this->assertFalse($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" IS NULL', $q->toString());
    }

    public function test_negate_like(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('name')->like('John%')->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::Like, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "name" NOT LIKE \'John%\'', $q->toString());
    }

    public function test_negate_in(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->in([1, 2])->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::In, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT IN (1, 2)', $q->toString());
    }

    public function test_negate_between(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->between(1, 10)->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::Between, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" NOT BETWEEN 1 AND 10', $q->toString());
    }

    public function test_negate_inRange(): void
    {
        $conn = $this->sqliteConnection();
        $cond = ConditionBuilder::for('b')->inRange(Bounds::excluded(1, 10))->negate();
        $def = $cond->getDefinition();
        $this->assertSame(Operator::Range, $def->operator);
        $this->assertTrue($def->negated);

        $q = $conn->query()->select()->from('t')->where($cond);
        $this->assertSame('SELECT * FROM "t" WHERE "b" <= 1 OR "b" >= 10', $q->toString());
    }

    public function test_getDefinition(): void
    {
        $cond = ConditionBuilder::for('id')->equals(1);
        $def = $cond->getDefinition();
        $this->assertInstanceOf(ConditionDefinition::class, $def);
    }

    public function test_define_more_than_once(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Tried to set condition when it was already set!');
        ConditionBuilder::for('id')->equals(1)->equals(2);
    }
}
