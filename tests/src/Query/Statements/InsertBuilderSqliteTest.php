<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Database\Functions\CurrentTimestamp;

class InsertBuilderSqliteTest extends InsertBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_insert_values_with_expression(): void
    {
        $syntax = $this->connect()->adapter->querySyntax;
        $statement = $this->insertBuilder('User')->values([['t'=> new CurrentTimestamp(), 'n' => 'a']]);
        $template = $statement->statement->generateTemplate($syntax);
        $this->assertSame('INSERT INTO "User" ("t", "n") VALUES (DATETIME("now", "localtime"), ?)', $template);
        $this->assertSame('INSERT INTO "User" ("t", "n") VALUES (DATETIME("now", "localtime"), \'a\')', $statement->toSql());
    }
}
