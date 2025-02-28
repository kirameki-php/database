<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Query\Statements\SortOrder;
use Tests\Kirameki\Database\DatabaseTestCase;

class SortOrderTest extends DatabaseTestCase
{
    public function test_reverse(): void
    {
        $asc = SortOrder::Ascending;
        $desc = SortOrder::Descending;
        $this->assertSame($desc, $asc->reverse());
        $this->assertSame($asc, $desc->reverse());
    }
}
