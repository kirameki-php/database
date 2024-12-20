<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Support;

use Kirameki\Database\Query\Statements\Bounds;
use Tests\Kirameki\Database\Query\QueryTestCase;

class BoundsTest extends QueryTestCase
{
    public function test_closed(): void
    {
        $bounds = Bounds::closed(1, 10);
        $this->assertSame(1, $bounds->lower);
        $this->assertTrue($bounds->lowerClosed);
        $this->assertSame(10, $bounds->upper);
        $this->assertTrue($bounds->upperClosed);
    }

    public function test_open(): void
    {
        $bounds = Bounds::open(1, 10);
        $this->assertSame(1, $bounds->lower);
        $this->assertFalse($bounds->lowerClosed);
        $this->assertSame(10, $bounds->upper);
        $this->assertFalse($bounds->upperClosed);
    }

    public function test_halfOpen(): void
    {
        $bounds = Bounds::halfOpen(1, 10);
        $this->assertSame(1, $bounds->lower);
        $this->assertTrue($bounds->lowerClosed);
        $this->assertSame(10, $bounds->upper);
        $this->assertFalse($bounds->upperClosed);
    }

    public function test_included(): void
    {
        $bounds = Bounds::included(1, 10);
        $this->assertSame(1, $bounds->lower);
        $this->assertTrue($bounds->lowerClosed);
        $this->assertSame(10, $bounds->upper);
        $this->assertTrue($bounds->upperClosed);
    }

    public function test_excluded(): void
    {
        $bounds = Bounds::excluded(1, 10);
        $this->assertSame(1, $bounds->lower);
        $this->assertFalse($bounds->lowerClosed);
        $this->assertSame(10, $bounds->upper);
        $this->assertFalse($bounds->upperClosed);
    }

    public function test_endExcluded(): void
    {
        $bounds = Bounds::endExcluded(1, 10);
        $this->assertSame(1, $bounds->lower);
        $this->assertTrue($bounds->lowerClosed);
        $this->assertSame(10, $bounds->upper);
        $this->assertFalse($bounds->upperClosed);
    }

    public function test_iterator(): void
    {
        $bounds = Bounds::closed(1, 10);
        $this->assertSame([1, 10], iterator_to_array($bounds));
    }

    public function test_getLowerOperator(): void
    {
        $bounds = Bounds::closed(1, 10);
        $this->assertSame('>=', $bounds->getLowerOperator());
        $this->assertSame('>=', $bounds->getLowerOperator(false));
        $this->assertSame('<', $bounds->getLowerOperator(true));
        $bounds = Bounds::open(1, 10);
        $this->assertSame('>', $bounds->getLowerOperator());
        $this->assertSame('>', $bounds->getLowerOperator(false));
        $this->assertSame('<=', $bounds->getLowerOperator(true));
        $bounds = Bounds::halfOpen(1, 10);
        $this->assertSame('>=', $bounds->getLowerOperator());
        $this->assertSame('>=', $bounds->getLowerOperator(false));
        $this->assertSame('<', $bounds->getLowerOperator(true));
        $bounds = Bounds::included(1, 10);
        $this->assertSame('>=', $bounds->getLowerOperator());
        $this->assertSame('>=', $bounds->getLowerOperator(false));
        $this->assertSame('<', $bounds->getLowerOperator(true));
        $bounds = Bounds::excluded(1, 10);
        $this->assertSame('>', $bounds->getLowerOperator());
        $this->assertSame('>', $bounds->getLowerOperator(false));
        $this->assertSame('<=', $bounds->getLowerOperator(true));
        $bounds = Bounds::endExcluded(1, 10);
        $this->assertSame('>=', $bounds->getLowerOperator());
        $this->assertSame('>=', $bounds->getLowerOperator(false));
        $this->assertSame('<', $bounds->getLowerOperator(true));
    }

    public function test_getUpperOperator(): void
    {
        $bounds = Bounds::closed(1, 10);
        $this->assertSame('<=', $bounds->getUpperOperator());
        $this->assertSame('<=', $bounds->getUpperOperator(false));
        $this->assertSame('>', $bounds->getUpperOperator(true));
        $bounds = Bounds::open(1, 10);
        $this->assertSame('<', $bounds->getUpperOperator());
        $this->assertSame('<', $bounds->getUpperOperator(false));
        $this->assertSame('>=', $bounds->getUpperOperator(true));
        $bounds = Bounds::halfOpen(1, 10);
        $this->assertSame('<', $bounds->getUpperOperator());
        $this->assertSame('<', $bounds->getUpperOperator(false));
        $this->assertSame('>=', $bounds->getUpperOperator(true));
        $bounds = Bounds::included(1, 10);
        $this->assertSame('<=', $bounds->getUpperOperator());
        $this->assertSame('<=', $bounds->getUpperOperator(false));
        $this->assertSame('>', $bounds->getUpperOperator(true));
        $bounds = Bounds::excluded(1, 10);
        $this->assertSame('<', $bounds->getUpperOperator());
        $this->assertSame('<', $bounds->getUpperOperator(false));
        $this->assertSame('>=', $bounds->getUpperOperator(true));
        $bounds = Bounds::endExcluded(1, 10);
        $this->assertSame('<', $bounds->getUpperOperator());
        $this->assertSame('<', $bounds->getUpperOperator(false));
        $this->assertSame('>=', $bounds->getUpperOperator(true));
    }
}
