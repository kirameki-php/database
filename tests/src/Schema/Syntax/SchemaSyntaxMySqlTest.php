<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema\Syntax;

class SchemaSyntaxMySqlTest extends SchemaSyntaxTestAbstract
{
    protected string $connection = 'mysql';

    public function test_supportsDdlTransaction(): void
    {
        $this->assertFalse($this->connect()->adapter->schemaSyntax->supportsDdlTransaction());
    }
}
