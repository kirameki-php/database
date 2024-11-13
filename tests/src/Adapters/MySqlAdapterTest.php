<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Schema\Statements\RawStatement as SchemaRawStatement;
use Kirameki\Database\Transaction\Support\IsolationLevel;
use Override;

class MySqlAdapterTest extends PdoAdapterTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_createDatabase_with_ifNotExist_disabled(): void
    {
        $adapter = $this->createAdapter();
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
        $adapter->createDatabase(false);
        $this->assertTrue($adapter->databaseExists());

        $database = $adapter->connectionConfig->getTableSchema();
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("SQLSTATE[HY000]: General error: 1007 Can't create database '{$database}'; database exists");
        $adapter->createDatabase(false);
        $this->assertTrue($adapter->databaseExists());
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
        $name = 'test_table' . random_int(1, 1000);
        $adapter1 = $this->createMySqlAdapter($name);
        $adapter1->createDatabase();
        $this->runAfterTearDown(static fn() => $adapter1->dropDatabase());

        $adapter1->runSchema(new SchemaRawStatement('CREATE TABLE test_table (id INT PRIMARY KEY, name VARCHAR(255))'));
        $adapter1->runQuery(new RawStatement('INSERT INTO test_table (id, name) VALUES (1, "a")'));
        $adapter1->beginTransaction(IsolationLevel::Serializable);
        $adapter1->runQuery(new RawStatement('UPDATE test_table SET name = "b" WHERE id = 1'));

        $adapter2 = $this->createMySqlAdapter($name);
        $adapter2->beginTransaction(IsolationLevel::ReadUncommitted);
        $result = $adapter2->runQuery(new RawStatement('SELECT * FROM test_table'));
        $this->assertSame('b', $result->first()->name);
    }
}
