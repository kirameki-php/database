<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use function dump;

class UpsertBuilderSqliteTest extends UpsertBuilderTestAbstract
{
    protected string $useConnection = 'sqlite';

    public function test_upsert_value(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->string('name');
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->value(['name' => 'a']);
        $select = $query->upsertInto('User')
            ->value(['id'=> 1, 'name' => 'b'])
            ->value(['id'=> 2, 'name' => 'c']);
        $this->assertSame('INSERT INTO "User" ("id", "name") VALUES (1, \'b\'), (2, \'c\') ON CONFLICT DO UPDATE SET "id" = EXCLUDED."id", "name" = EXCLUDED."name"', $select->toSql());
        $this->assertSame(2, $select->execute()->affectedRowCount);
        $result = $query->select()->from('User')->execute()->all();
        $this->assertCount(2, $result);
        $this->assertSame(['id' => 1, 'name' => 'b'],  (array) $result[0]);
        $this->assertSame(['id' => 2, 'name' => 'c'],  (array) $result[1]);
    }

    public function test_upsert_values(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->string('name');
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->value(['name' => 'a']);
        $select = $query->upsertInto('User')->values([
            ['id'=> 1, 'name' => 'b'],
            ['id'=> 2, 'name' => 'c'],
        ]);
        $this->assertSame('INSERT INTO "User" ("id", "name") VALUES (1, \'b\'), (2, \'c\') ON CONFLICT DO UPDATE SET "id" = EXCLUDED."id", "name" = EXCLUDED."name"', $select->toSql());
        $this->assertSame(2, $select->execute()->affectedRowCount);
        $result = $query->select()->from('User')->execute()->all();
        $this->assertCount(2, $result);
        $this->assertSame(['id' => 1, 'name' => 'b'],  (array) $result[0]);
        $this->assertSame(['id' => 2, 'name' => 'c'],  (array) $result[1]);
    }

    public function test_upsert_onConflict(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->string('name');
        $table->uniqueIndex(['name']);
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->value(['name' => 'a']);
        $select = $query->upsertInto('User')->onConflict('name')->values([
            ['id'=> 3, 'name' => 'a'],
            ['id'=> 2, 'name' => 'c'],
        ]);
        $this->assertSame('INSERT INTO "User" ("id", "name") VALUES (3, \'a\'), (2, \'c\') ON CONFLICT ("name") DO UPDATE SET "id" = EXCLUDED."id", "name" = EXCLUDED."name"', $select->toSql());
        $this->assertSame(2, $select->execute()->affectedRowCount);
        $result = $query->select()->from('User')->execute()->all();
        $this->assertCount(2, $result);
        $this->assertSame(['id' => 2, 'name' => 'c'],  (array) $result[0]);
        $this->assertSame(['id' => 3, 'name' => 'a'],  (array) $result[1]);
    }

    public function test_upsert_returning(): void
    {
        $conn = $this->connect();
        $table = $conn->schema()->createTable('User');
        $table->id();
        $table->string('name');
        $table->execute();

        $query = $conn->query();
        $query->insertInto('User')->value(['name' => 'a']);
        $select = $query->upsertInto('User')->returning('id')->values([
            ['id'=> 1, 'name' => 'b'],
            ['id'=> 2, 'name' => 'c'],
        ]);
        $this->assertSame('INSERT INTO "User" ("id", "name") VALUES (1, \'b\'), (2, \'c\') ON CONFLICT DO UPDATE SET "id" = EXCLUDED."id", "name" = EXCLUDED."name" RETURNING "id"', $select->toSql());
        $this->assertSame([1, 2], $select->execute()->pluck('id')->all());
    }
}
