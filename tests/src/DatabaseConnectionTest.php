<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Exception;
use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Events\ConnectionEstablished;
use Kirameki\Database\Exceptions\LockException;
use Kirameki\Database\Info\InfoHandler;
use Kirameki\Database\Query\QueryHandler;
use Kirameki\Database\Query\Statements\LockOption;
use Kirameki\Database\Schema\SchemaHandler;
use Kirameki\Database\Transaction\IsolationLevel;
use Kirameki\Database\Transaction\TransactionContext;
use Kirameki\Database\Transaction\TransactionInfo;
use Kirameki\Database\Transaction\TransactionOptions;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function iterator_to_array;
use function mt_rand;
use const INF;

class DatabaseConnectionTest extends QueryTestCase
{
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
        $this->assertCount(1, $this->capturedEvents);

        $connection->reconnect();
        $this->assertTrue($connection->isConnected(), 'reconnect while connected');
        $this->assertCount(2, $this->capturedEvents);
    }

    public function test_connect__valid(): void
    {
        $this->captureEvents(ConnectionEstablished::class);

        $connection = $this->createTempConnection('sqlite');
        $this->assertSame($connection, $connection->disconnect()->connect());
        $this->assertTrue($connection->isConnected());
        $this->assertCount(1, $this->capturedEvents);
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

    public function test_tags(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $tags = $connection->tags;
        $this->assertSame([], iterator_to_array($tags));
        $this->assertSame($tags, $connection->tags, 'test cached');
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

    public function test_transaction__rollback_nested_deep(): void
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
                    throw new Exception('rollback');
                });
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

    public function test_transaction__rollback_nested_after_unnested(): void
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

    public function test_transaction__connection_not_shared(): void
    {
        $this->expectException(LockException::class);
        $this->expectExceptionMessage('Statement aborted because lock(s) could not be acquired immediately and NOWAIT is set.');

        $database = 'test_' . mt_rand();
        $connectionConfig = new MySqlConfig(host: 'mysql', database: $database);
        $adapter = $this->createMySqlAdapter($database, null, $connectionConfig);
        $connection1 = $this->createTempConnection('mysql', $adapter);
        $connection2 = $this->createTempConnection('mysql', clone $adapter);
        $table = $connection1->schema()->createTable('t');
        $table->id();
        $table->string('name', 1)->nullable();
        $table->execute();

        $query1 = $connection1->reconnect()->query();
        $query2 = $connection2->query();

        $connection1->transaction(function() use ($query1) {
            $query1->insertInto('t')->values([['id' => 1], ['id' => 2]])->execute();
        });

        $connection1->transaction(function() use ($connection2, $query1, $query2) {
            $connection2->transaction(function() use ($query1, $query2) {
                $query1->select()->from('t')->where('id', 1)->forUpdate()->first();
                $query2->select()->from('t')->where('id', 1)->forUpdate(LockOption::Nowait)->first();
            });
        });
    }

    public function test_transaction__lock_timeout(): void
    {
        $this->expectException(LockException::class);
        $this->expectExceptionMessage('Lock wait timeout exceeded; try restarting transaction');

        $database = 'test_' . mt_rand();
        $adapter = $this->createMySqlAdapter($database, null, new MySqlConfig(
            host: 'mysql',
            database: $database,
            transactionLockWaitTimeoutSeconds: 1,
        ));
        $connection1 = $this->createTempConnection('mysql', $adapter);
        $connection2 = $this->createTempConnection('mysql', clone $adapter);
        $table = $connection1->schema()->createTable('t');
        $table->id();
        $table->string('name', 1)->nullable();
        $table->execute();

        $query1 = $connection1->reconnect()->query();
        $query2 = $connection2->query();

        $connection1->transaction(function() use ($query1) {
            $query1->insertInto('t')->values([['id' => 1], ['id' => 2]])->execute();
        });

        $connection1->transaction(function() use ($connection2, $query1, $query2) {
            $connection2->transaction(function() use ($query1, $query2) {
                $query1->select()->from('t')->where('id', 1)->forUpdate()->first();
                $query2->select()->from('t')->where('id', 1)->forUpdate()->first();
            });
        });
    }

    public function test_transactionInfo(): void
    {
        $connection = $this->createTempConnection('sqlite');
        $this->assertNull($connection->transactionInfo);
        $connection->transaction(function() use ($connection) {
            $this->assertInstanceOf(TransactionContext::class, $connection->transactionInfo);
        });
    }
}
