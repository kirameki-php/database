<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\NullOrder;
use Tests\Kirameki\Database\DatabaseTestCase;

class NullOrderTest extends DatabaseTestCase
{
    public function test_reverse(): void
    {
        $first = NullOrder::First;
        $last = NullOrder::Last;
        $this->assertSame($last, $first->reverse());
        $this->assertSame($first, $last->reverse());
    }
}
