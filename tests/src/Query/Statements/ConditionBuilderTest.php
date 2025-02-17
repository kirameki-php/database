<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\Bounds;
use Kirameki\Database\Query\Statements\ConditionBuilder;
use Kirameki\Database\Query\Statements\Operator;
use Kirameki\Database\Raw;
use Tests\Kirameki\Database\Query\QueryTestCase;

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

    public function test_raw(): void
    {
        $def = ConditionBuilder::raw('id = 1')->getDefinition();
        $this->assertSame('_UNUSED_', $def->column);
        $this->assertInstanceOf(Raw::class, $def->value);
        $this->assertSame(Operator::Raw, $def->operator);
        $this->assertFalse($def->negated);
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
}
