<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Schema\Statements\RawStatement as SchemaRawStatement;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Tests\Kirameki\Database\DatabaseTestCase;
use function rand;

class MySqlAdapterTest extends DatabaseTestCase
{
    public function test_connect(): void
    {
        $adapter = $this->createMySqlAdapter();
        $adapter->createDatabase(true);
        $adapter->connect();
        $this->assertTrue($adapter->isConnected());
    }

    public function test_disconnect(): void
    {
        $adapter = $this->createMySqlAdapter();
        $adapter->createDatabase(true);
        $this->assertFalse($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
        $adapter->connect();
        $this->assertTrue($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
    }

    public function test_isConnected(): void
    {
        $adapter = $this->createMySqlAdapter();
        $this->assertFalse($adapter->isConnected());
        $adapter->createDatabase(true);
        $adapter->connect();
        $this->assertTrue($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
    }

    public function test_runSchema(): void
    {
        $tableName = 'test_table_' . rand(1000, 9999);
        $connection = $this->createTempConnection('mysql');
        $adapter = $connection->adapter;
        $adapter->createDatabase(true);
        $adapter->connect();
        $result = $adapter->runSchema(new SchemaRawStatement("CREATE TABLE {$tableName} (id INT PRIMARY KEY)"));
        $this->assertInstanceOf(SchemaResult::class, $result);
        $this->assertInstanceOf(SchemaRawStatement::class, $result->statement);
        $this->assertSame(["CREATE TABLE {$tableName} (id INT PRIMARY KEY)"], $result->commands);
        $this->assertTrue($connection->info()->tableExists($tableName));
    }

    public function test_runSchema_invalid_syntax(): void
    {
        $connection = $this->createTempConnection('mysql');
        $adapter = $connection->adapter;
        $adapter->createDatabase(true);
        $adapter->connect();

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax;');
        $adapter->runSchema(new SchemaRawStatement("HELLO"));
    }

    public function test_runQuery(): void
    {
        $connection = $this->createTempConnection('mysql');
        $adapter = $connection->adapter;
        $adapter->createDatabase(true);
        $adapter->connect();
        $result = $adapter->runQuery(new RawStatement('SELECT ?', [1]));
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertInstanceOf(RawStatement::class, $result->statement);
        $this->assertSame('SELECT ?', $result->template);
        $this->assertSame([1], $result->parameters);
        $this->assertSame(1, $result->getAffectedRowCount());
    }

    public function test_runQuery_invalid_syntax(): void
    {
        $connection = $this->createTempConnection('mysql');
        $adapter = $connection->adapter;
        $adapter->createDatabase(true);
        $adapter->connect();
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax;');
        $adapter->runQuery(new RawStatement('HELLO'));
    }
}
