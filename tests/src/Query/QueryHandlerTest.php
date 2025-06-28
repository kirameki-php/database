<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Events\QueryExecuted;
use Kirameki\Database\Query\Statements\DeleteBuilder;
use Kirameki\Database\Query\Statements\InsertBuilder;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Statements\SelectBuilder;
use Kirameki\Database\Query\Statements\Tags;
use Kirameki\Database\Query\Statements\UpdateBuilder;
use Kirameki\Database\Query\Statements\UpsertBuilder;
use Kirameki\Event\Event;
use Kirameki\Time\Time;
use Tests\Kirameki\Database\Query\_Support\StatusEnum;

class QueryHandlerTest extends QueryTestCase
{
    /**
     * @var array<int, Event>
     */
    protected array $eventTriggers = [];

    protected function listenToQueryExecuted(): void
    {
        $this->getEventManager()->on(
            QueryExecuted::class,
            fn (QueryExecuted $e) => $this->eventTriggers[] = $e,
        );
    }

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

    public function test_raw(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->value(['id' => 1])
            ->execute();

        $this->listenToQueryExecuted();

        $handler = $connection->query();
        $connection->tags->set('a', 1);
        $result = $handler->raw('SELECT * FROM users')->execute();
        $this->assertSame('SELECT * FROM users /* a=1 */', $result->template);
        $this->assertSame([], $result->parameters);
        $this->assertSame(['id' => 1], (array) $result->first());
        $this->assertCount(1, $this->eventTriggers);
    }

    public function test_raw_with_parameters(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->value(['id' => 1])
            ->execute();

        $handler = $connection->query();
        $connection->tags->set('a', 1);
        $result = $handler->raw('SELECT * FROM users WHERE id = ?', [1])->execute();
        $this->assertSame('SELECT * FROM users WHERE id = ? /* a=1 */', $result->template);
        $this->assertSame([1], $result->parameters);
        $this->assertSame(['id' => 1], (array) $result->first());
    }

    public function test_raw_with_casts(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->timestamp('time');
        $table->int('status');
        $table->execute();

        $time = new Time('2021-01-01 00:00:00.000');
        $status = StatusEnum::Active;
        $connection->query()->insertInto('users')
            ->value(['id' => 1, 'time' => $time, 'status' => $status])
            ->execute();

        $handler = $connection->query();
        $result = $handler->raw('SELECT * FROM users')->casts([
            'time' => Time::class,
            'status' => StatusEnum::class,
        ])->execute();
        $this->assertSame('SELECT * FROM users', $result->template);
        $this->assertSame([], $result->parameters);
        $this->assertEquals(['id' => 1, 'time' => $time, 'status' => $status], (array) $result->first());
    }

    public function test_raw_with_tags(): void
    {
        $connection = $this->sqliteConnection();
        $table = $connection->schema()->createTable('users');
        $table->id();
        $table->execute();

        $connection->query()->insertInto('users')
            ->value(['id' => 1])
            ->execute();

        $handler = $connection->query();
        $connection->tags->set('a', 1);
        $result = $handler->raw('SELECT * FROM users')->setTag('b', 2)->execute();
        $this->assertSame('SELECT * FROM users /* b=2,a=1 */', $result->template);
        $this->assertSame([], $result->parameters);
        $this->assertSame(['id' => 1], (array) $result->first());
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

        $this->listenToQueryExecuted();

        $statement = new RawStatement('SELECT * FROM users');
        $connection->tags->set('a', 1);
        $statement->tags = new Tags(['b' => 2]);
        $handler = $connection->query();
        $result = $handler->execute($statement);
        $this->assertSame('SELECT * FROM users /* b=2,a=1 */', $result->template);
        $this->assertSame(['id' => 1], (array) $result->first());
        $this->assertCount(1, $this->eventTriggers);
    }

    public function test_execute__without_tags(): void
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
        $result = $handler->execute($statement);
        $this->assertSame('SELECT * FROM users', $result->template);
        $this->assertSame(null, $result->statement->tags);
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

        $this->listenToQueryExecuted();

        $statement = new RawStatement('SELECT * FROM users');
        $handler = $connection->query();
        $cursor = $handler->cursor($statement);
        $results = [];
        foreach ($cursor as $result) {
            $results[] = (array) $result;
        }
        $this->assertSame([['id' => 1], ['id' => 2]], $results);
        $this->assertCount(1, $this->eventTriggers);
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

        $this->listenToQueryExecuted();

        $statement = new RawStatement('SELECT * FROM users');
        $handler = $connection->query();
        $result = $handler->explain($statement);
        $this->assertSame((array) $result->first(), [
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
        $this->assertCount(1, $this->eventTriggers);
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

        $this->listenToQueryExecuted();

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
        $this->assertCount(1, $this->eventTriggers);
    }

    public function test_toSql(): void
    {
        $connection = $this->sqliteConnection();
        $connection->tags->set('a', 1);
        $statement = new RawStatement('SELECT * FROM users');
        $statement->tags = new Tags(['b' => 2]);
        $result = $connection->query()->toSql($statement);
        $this->assertSame('SELECT * FROM users /* b=2 */', $result);
    }
}
