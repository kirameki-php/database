<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Core\Exceptions\InvalidConfigException;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Schema\Statements\RawStatement as SchemaRawStatement;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Override;
use PDOException;

class MySqlAdapterTest extends PdoAdapterTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_connect_with_no_host_or_socket(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Either host or socket must be defined.');
        (new MySqlAdapter(new DatabaseConfig([]), new MySqlConfig()))->connect();
    }

    public function test_connect_with_both_host_and_socket(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Host and socket cannot be used together.');
        (new MySqlAdapter(new DatabaseConfig([]), new MySqlConfig(host: 'abc', socket: '123')))->connect();
    }

    public function test_connect_try_with_socket(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('SQLSTATE[HY000] [2002] No such file or directory');
        (new MySqlAdapter(new DatabaseConfig([]), new MySqlConfig(socket: '/run/mysql.sock')))->connect();
    }

    #[Override]
    public function test_connect_as_readOnly(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[25006]: Read only sql transaction: 1792 Cannot execute statement in a READ ONLY transaction.');
        $adapter = $this->createMySqlAdapter();
        $adapter->createDatabase();
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test (id INT PRIMARY KEY, name VARCHAR(10))'));
        $adapter->disconnect();
        $adapter->connectionConfig->readOnly = true;
        $adapter->runQuery(new RawStatement('INSERT INTO test (id, name) VALUES (1, "a")'));
    }

    public function test_dropDatabase_database_ifExists_disabled(): void
    {
        $adapter = $this->createAdapter();
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
        $adapter->dropDatabase(false);

        $database = $adapter->connectionConfig->getTableSchema();
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("General error: 1008 Can't drop database '{$database}'; database doesn't exist");
        $adapter->dropDatabase(false);
    }

    #[Override]
    public function test_beginTransaction_with_isolation_level(): void
    {
        $name = 'test_' . random_int(1, 1000);
        $adapter1 = $this->createMySqlAdapter($name);
        $adapter1->createDatabase();
        $this->runAfterTearDown(static fn() => $adapter1->dropDatabase());

        $adapter1->runSchema(new SchemaRawStatement('CREATE TABLE test (id INT PRIMARY KEY, name VARCHAR(255))'));
        $adapter1->runQuery(new RawStatement('INSERT INTO test (id, name) VALUES (1, "a")'));
        $adapter1->beginTransaction(IsolationLevel::Serializable);
        $adapter1->runQuery(new RawStatement('UPDATE test SET name = "b" WHERE id = 1'));

        $adapter2 = $this->createMySqlAdapter($name);
        $adapter2->beginTransaction(IsolationLevel::ReadUncommitted);
        $result = $adapter2->runQuery(new RawStatement('SELECT * FROM test'));
        $this->assertSame('b', $result->first()->name);
    }
}
