<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Kirameki\Time\Time;

class InsertBuilderSqliteTest extends InsertBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_insert_value(): void
    {
        $sql = $this->insertBuilder('User')->value(['status'=> 1, 'name' => 'abc'])->toString();
        $this->assertSame('INSERT INTO "User" ("status", "name") VALUES (1, \'abc\')', $sql);
    }

    public function test_insert_values(): void
    {
        $sql = $this->insertBuilder('User')->values([['name' => 'abc'], ['name' => 'def']])->toString();
        $this->assertSame('INSERT INTO "User" ("name") VALUES (\'abc\'), (\'def\')', $sql);
    }

    public function test_insert_partial_values(): void
    {
        $sql = $this->insertBuilder('User')->values([['status'=> 1], ['name' => 'abc']])->toString();
        $this->assertSame('INSERT INTO "User" ("status", "name") VALUES (1, DEFAULT), (DEFAULT, \'abc\')', $sql);
    }

    public function test_insert_integer(): void
    {
        $sql = $this->insertBuilder('User')->values([['id' => 1], ['id' => 2]])->toString();
        $this->assertSame('INSERT INTO "User" ("id") VALUES (1), (2)', $sql);
    }

    public function test_insert_string(): void
    {
        $sql = $this->insertBuilder('User')->values([['name' => 'a'], ['name' => 'b']])->toString();
        $this->assertSame('INSERT INTO "User" ("name") VALUES (\'a\'), (\'b\')', $sql);
    }

    public function test_insert_DateTime(): void
    {
        $sql = $this->insertBuilder('User')->value(['createdAt' => new Time('2020-01-01T01:12:34.56789Z')])->toString();
        $this->assertSame('INSERT INTO "User" ("createdAt") VALUES (\'2020-01-01T01:12:34.567890+00:00\')', $sql);
    }

    public function test_returning(): void
    {
        $sql = $this->insertBuilder('User')->value(['id'=> 1, 'name' => 'abc'])->returning('id', 'name')->toString();
        $this->assertSame('INSERT INTO "User" ("id", "name") VALUES (1, \'abc\') RETURNING "id", "name"', $sql);
    }
}
