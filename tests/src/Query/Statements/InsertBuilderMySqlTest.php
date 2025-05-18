<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Functions\CurrentTimestamp;

class InsertBuilderMySqlTest extends InsertBuilderTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_insert_values_with_expression(): void
    {
        $syntax = $this->connect()->adapter->querySyntax;
        $statement = $this->insertBuilder('User')->values([['t'=> new CurrentTimestamp(), 'n' => 'a']]);
        $template = $statement->statement->generateTemplate($syntax);
        $this->assertSame('INSERT INTO "User" ("t", "n") VALUES (CURRENT_TIMESTAMP(), ?)', $template);
        $this->assertSame('INSERT INTO "User" ("t", "n") VALUES (CURRENT_TIMESTAMP(), \'a\')', $statement->toSql());
    }
}
