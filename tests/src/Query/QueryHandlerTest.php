<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Kirameki\Database\Query\Statements\UpsertBuilder;
use function dump;

class QueryHandlerTest extends QueryTestCase
{
    public function test_select(): void
    {
        $handler = $this->sqliteConnection()->query();
        $builder = $handler->select('*');
        $this->assertInstanceOf(SelectBuilder::class, $builder);
    }

    public function test_insertInto(): void
    {
        $handler = $this->sqliteConnection()->query();
        $builder = $handler->insertInto('users');
        $this->assertInstanceOf(InsertBuilder::class, $builder);
    }

    public function test_update(): void
    {
        $handler = $this->sqliteConnection()->query();
        $builder = $handler->update('users');
        $this->assertInstanceOf(UpdateBuilder::class, $builder);
    }

    public function test_upsertInto(): void
    {
        $handler = $this->sqliteConnection()->query();
        $builder = $handler->upsertInto('users');
        $this->assertInstanceOf(UpsertBuilder::class, $builder);
    }

    public function test_delete(): void
    {
        $handler = $this->sqliteConnection()->query();
        $builder = $handler->deleteFrom('users');
        $this->assertInstanceOf(DeleteBuilder::class, $builder);
    }

    public function test_execute(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->value(['id' => 1])
            ->execute();

        $statement = new RawStatement('SELECT * FROM users');
        $handler = $connection->query();
        $result = (array) $handler->execute($statement)->first();
        $this->assertEquals(['id' => 1], $result);
    }

    public function test_executeRaw(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->value(['id' => 1])
            ->execute();

        $handler = $connection->query();
        $result = (array) $handler->executeRaw('SELECT * FROM users')->first();
        $this->assertEquals(['id' => 1], $result);
    }

    public function test_cursor(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->values([['id' => 1], ['id' => 2]])
            ->execute();

        $statement = new RawStatement('SELECT * FROM users');
        $handler = $connection->query();
        $cursor = $handler->cursor($statement);
        $results = [];
        foreach ($cursor as $result) {
            $results[] = (array) $result;
        }
        $this->assertEquals([['id' => 1], ['id' => 2]], $results);
    }

    public function test_explain_mysql(): void
    {
        $connection = $this->mysqlConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->value(['id' => 1])
            ->execute();

        $statement = new RawStatement('SELECT * FROM users');
        $handler = $connection->query();
        $result = $handler->explain($statement)->all();
        $this->assertSame((array) $result[0], [
            'id' => 1,
            'select_type' => 'SIMPLE',
            'table' => 'users',
            'partitions' => null,
            'type' => 'index',
            'possible_keys' => null,
            'key' => 'PRIMARY',
            'key_len' => '8',
            'ref' => null,
            'rows' => 1,
            'filtered' => 100.0,
            'Extra' => 'Using index',
        ]);
    }

    public function test_explain_sqlite(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->value(['id' => 1])
            ->execute();

        $statement = new RawStatement('SELECT * FROM users');
        $handler = $connection->query();
        $result = $handler->explain($statement)->all();
        $this->assertSame((array) $result[0], [
            'addr' => 0,
            'opcode' => 'Init',
            'p1' => 0,
            'p2' => 7,
            'p3' => 0,
            'p4' => null,
            'p5' => 0,
            'comment' => null,
        ]);
    }

    public function test_toString(): void
    {
        $connection = $this->sqliteConnection();
        $statement = new RawStatement('SELECT * FROM users');
        $result = $connection->query()->toString($statement);
        $this->assertSame('SELECT * FROM users', $result);
    }
}
