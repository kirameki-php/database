<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema\Syntax;

class SchemaSyntaxSqliteTest extends SchemaSyntaxTestAbstract
{
    protected string $connection = 'sqlite';

    public function test_supportsDdlTransaction(): void
    {
        $this->assertTrue($this->connect()->adapter->schemaSyntax->supportsDdlTransaction());
    }
}
