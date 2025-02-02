<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Exception;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Events\ConnectionEstablished;
use Kirameki\Database\Info\InfoHandler;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Transaction\IsolationLevel;
use Kirameki\Database\Transaction\TransactionContext;
use Kirameki\Database\Transaction\TransactionInfo;
use Kirameki\Database\Transaction\TransactionOptions;
use Kirameki\Event\Event;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function iterator_to_array;
use const INF;

class ConnectionTest extends QueryTestCase
{
    /**
     * @var array<class-string<Event>, list<Event>>
     */
    protected array $capturedEvents = [];

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $class
     */
    protected function captureEvents(string $class): void
    {
        $this->getEventManager()->on($class, fn(Event $e) => $this->capturedEvents[$e::class][] = $e);
    }

    public function test_properties(): void
    {
        $connection = $this->createTempConnection('sqlite');

        $this->assertSame('temp', $connection->name);
        $this->assertInstanceOf(SqliteAdapter::class, $connection->adapter);
    }

    public function test_reconnect(): void
    {
        $this->captureEvents(ConnectionEstablished::class);

        $connection = $this->createTempConnection('sqlite');
        $connection->disconnect();
        $this->assertFalse($connection->isConnected(), 'ensure not connected');

        $this->assertSame($connection, $connection->reconnect());
        $this->assertTrue($connection->isConnected(), 'reconnect while disconnected');
        $this->assertCount(1, $this->capturedEvents[ConnectionEstablished::class]);

        $connection->reconnect();
        $this->assertTrue($connection->isConnected(), 'reconnect while connected');
        $this->assertCount(2, $this->capturedEvents[ConnectionEstablished::class]);
    }

    public function test_connect__valid(): void
    {
        $this->captureEvents(ConnectionEstablished::class);

        $connection = $this->createTempConnection('sqlite');
        $this->assertSame($connection, $connection->disconnect()->connect());
        $this->assertTrue($connection->isConnected());
        $this->assertCount(1, $this->capturedEvents[ConnectionEstablished::class]);
    }

    public function test_connect__while_connected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Connection: "temp" is already established.');
        $connection = $this->createTempConnection('sqlite');
        $connection->connect();
    }

    public function test_connectIfNotConnected(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $connection->disconnect();
        $this->assertTrue($connection->connectIfNotConnected());
        $this->assertFalse($connection->connectIfNotConnected());
    }

    public function test_disconnect__valid(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $this->assertTrue($connection->isConnected());
        $this->assertSame($connection, $connection->disconnect());
        $this->assertFalse($connection->isConnected());
    }

    public function test_disconnect__while_disconnected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Connection: "temp" is not established.');
        $connection = $this->createTempConnection('sqlite');
        $this->assertSame($connection, $connection->disconnect());
        $connection->disconnect();
    }

    public function test_disconnectIfConnected(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $this->assertTrue($connection->isConnected());
        $this->assertTrue($connection->disconnectIfConnected());
        $this->assertFalse($connection->isConnected());
        $this->assertFalse($connection->disconnectIfConnected());
        $this->assertFalse($connection->isConnected());
    }

    public function test_isConnected(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $this->assertTrue($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    public function test_query(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $queryHandler = $connection->query();
        $this->assertInstanceOf(QueryHandler::class, $queryHandler);
        $this->assertSame($queryHandler, $connection->query(), 'test cached');
    }

    public function test_schema(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $schemaHandler = $connection->schema();
        $this->assertInstanceOf(SchemaHandler::class, $schemaHandler);
        $this->assertSame($schemaHandler, $connection->schema(), 'test cached');
    }

    public function test_info(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $infoHandler = $connection->info();
        $this->assertInstanceOf(InfoHandler::class, $infoHandler);
        $this->assertSame($infoHandler, $connection->info(), 'test cached');
    }

    public function test_getTags(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $tags = $connection->getTags();
        $this->assertSame([], iterator_to_array($tags));
        $this->assertSame($tags, $connection->getTags(), 'test cached');
    }

    public function test_transaction__simple(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $this->assertFalse($connection->inTransaction());
        $result = $connection->transaction(function(TransactionInfo $tx) use ($connection) {
            $this->assertInstanceOf(TransactionContext::class, $tx);
            $this->assertTrue($connection->inTransaction());
            return INF;
        });
        $this->assertInfinite($result);
        $this->assertFalse($connection->inTransaction());
    }

    public function test_transaction__nested(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $table = $connection->schema()->createTable('t');
        $table->id();
        $table->execute();

        $this->assertFalse($connection->inTransaction());
        $result = $connection->transaction(function(TransactionInfo $tx) use ($connection) {
            $r2 = $connection->transaction(function(TransactionInfo $tx2) use ($connection, $tx) {
                $this->assertSame($tx, $tx2);
                $this->assertTrue($connection->inTransaction());
                $connection->query()->insertInto('t')->value(['id' => 1])->execute();
                return INF;
            });
            $this->assertTrue($connection->inTransaction());
            $connection->query()->insertInto('t')->value(['id' => 2])->execute();
            return $r2;
        });
        $this->assertInfinite($result);
        $this->assertFalse($connection->inTransaction());
        $this->assertSame(2, $connection->query()->select()->from('t')->count());
    }

    public function test_transaction__rollback(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $this->assertFalse($connection->inTransaction());
        $called = false;
        $thrown = null;
        try {
            $connection->transaction(fn() => throw new Exception('rollback'));
        } catch (Exception $e) {
            $called = true;
            $thrown = $e;
        }
        $this->assertTrue($called);
        $this->assertInstanceOf(Exception::class, $thrown);
        $this->assertSame('rollback', $thrown->getMessage());
        $this->assertFalse($connection->inTransaction());
    }

    public function test_transaction__rollback_nested(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $table = $connection->schema()->createTable('t');
        $table->id();
        $table->execute();

        $called = false;
        $thrown = null;
        try {
            $connection->transaction(function() use ($connection) {
                $connection->transaction(function() use ($connection) {
                    $connection->query()->insertInto('t')->value(['id' => 1])->execute();
                });
                $connection->query()->insertInto('t')->value(['id' => 2])->execute();
                throw new Exception('rollback');
            });
        } catch (Exception $e) {
            $called = true;
            $thrown = $e;
        }
        $this->assertTrue($called);
        $this->assertInstanceOf(Exception::class, $thrown);
        $this->assertSame('rollback', $thrown->getMessage());
        $this->assertFalse($connection->inTransaction());
        $this->assertSame(0, $connection->query()->select()->from('t')->count());
    }

    public function test_transaction__nested_same_isolation_level(): void
    {
        $connection = $this->createTempConnection('mysql');
        $table = $connection->schema()->createTable('t');
        $table->id();
        $table->execute();

        $level = IsolationLevel::RepeatableRead;
        $connection->transaction(function() use ($connection, $level) {
            $connection->transaction(function() use ($connection) {
                $connection->query()->insertInto('t')->value(['id' => 1])->execute();
            }, new TransactionOptions($level));
            $connection->query()->insertInto('t')->value(['id' => 2])->execute();
        }, new TransactionOptions($level));
        $this->assertSame(2, $connection->query()->select()->from('t')->count());
    }

    public function test_transaction__nested_different_isolation_level(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Transaction isolation level mismatch. Expected: Serializable. Got: RepeatableRead');

        $connection = $this->createTempConnection('mysql');
        $table = $connection->schema()->createTable('t');
        $table->id();
        $table->execute();

        $connection->transaction(function() use ($connection) {
            $connection->transaction(function() use ($connection) {
                $connection->query()->insertInto('t')->value(['id' => 1])->execute();
            }, new TransactionOptions(isolationLevel: IsolationLevel::RepeatableRead));
            $connection->query()->insertInto('t')->value(['id' => 2])->execute();
        }, new TransactionOptions(isolationLevel: IsolationLevel::Serializable));
    }

    public function test_getTransactionInfoOrNull(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $this->assertNull($connection->getTransactionInfoOrNull());
        $connection->transaction(function() use ($connection) {
            $this->assertInstanceOf(TransactionContext::class, $connection->getTransactionInfoOrNull());
        });
    }
}
