<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Exceptions\NotSupportedException;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Exceptions\ConnectionException;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Schema\Statements\RawStatement as SchemaRawStatement;
use Kirameki\Database\Transaction\IsolationLevel;
use Kirameki\Database\Transaction\TransactionOptions;
use Override;

class SqliteAdapterTest extends PdoAdapterTestAbstract
{
    protected string $useConnection = 'sqlite';

    #[Override]
    public function test_connect_as_readOnly(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[HY000]: General error: 8 attempt to write a readonly database');
        $adapter = $this->createSqliteAdapter(connectionConfig: new SqliteConfig(filename: __DIR__.'/test.db'));
        $adapter->createDatabase();
        $this->runAfterTearDown(fn() => $adapter->dropDatabase());
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test (id INT PRIMARY KEY, name VARCHAR(10))'));
        $adapter->disconnect();
        $adapter->connectionConfig->readOnly = true;
        $adapter->runQuery(new RawStatement('INSERT INTO test (id, name) VALUES (1, "a")'));
    }

    #[Override]
    public function test_connect__failure_throws_ConnectionException(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('General error: 1 near "?": syntax error');
        $adapter = $this->createSqliteAdapter(connectionConfig: new SqliteConfig(':memory:', pragmas: ['fail' => '?']));
        $adapter->connect();
    }

    #[Override]
    public function test_beginTransaction_with_isolation_level(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Transaction Isolation level cannot be changed in SQLite.');
        $adapter = $this->connect()->adapter;
        $options = new TransactionOptions(
            isolationLevel: IsolationLevel::Serializable,
        );
        $adapter->beginTransaction($options);
    }
}
