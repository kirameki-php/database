<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Database\Connection;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Statements\RawStatement as SchemaRawStatement;
use Kirameki\Database\Schema\Statements\SchemaResult;
use stdClass;
use Tests\Kirameki\Database\DatabaseTestCase;
use function rand;

class SqliteAdapterTest extends DatabaseTestCase
{
    protected function createSqliteConnection(): Connection
    {
        $connection = $this->createTempConnection('sqlite');
        $adapter = $connection->adapter;
        $adapter->createDatabase(true);
        $adapter->connect();
        return $connection;
    }

    public function test_connect(): void
    {
        $connection = $this->createSqliteConnection();
        $this->assertTrue($connection->adapter->isConnected());
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

    public function test_runSchema_with_valid_statement(): void
    {
        $tableName = 'test_table_' . rand(1000, 9999);
        $connection = $this->createSqliteConnection();
        $adapter = $connection->adapter;
        $result = $adapter->runSchema(new SchemaRawStatement("CREATE TABLE {$tableName} (id INT PRIMARY KEY)"));
        $this->assertInstanceOf(SchemaResult::class, $result);
        $this->assertInstanceOf(SchemaRawStatement::class, $result->statement);
        $this->assertSame(["CREATE TABLE {$tableName} (id INT PRIMARY KEY)"], $result->commands);
        $this->assertTrue($connection->info()->tableExists($tableName));
    }

    public function test_runSchema_invalid_syntax(): void
    {
        $adapter = $this->createSqliteConnection()->adapter;
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('SQLSTATE[HY000]: General error: 1 near "HELLO": syntax error');
        $adapter->runSchema(new SchemaRawStatement("HELLO"));
    }

    public function test_runQuery_with_valid_statement(): void
    {
        $adapter = $this->createSqliteConnection()->adapter;
        $result = $adapter->runQuery(new RawStatement('SELECT ?', [1]));
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertInstanceOf(RawStatement::class, $result->statement);
        $this->assertSame('SELECT ?', $result->template);
        $this->assertSame([1], $result->parameters);
    }

    public function test_runQuery_invalid_syntax(): void
    {
        $adapter = $this->createSqliteConnection()->adapter;
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[HY000]: General error: 1 near "HELLO": syntax error');
        $adapter->runQuery(new RawStatement('HELLO'));
    }

    public function test_runQuery_with_normalize(): void
    {
        $statement = new class('SELECT 1 as a') extends RawStatement implements Normalizable {
            public function normalize(QuerySyntax $syntax, stdClass $row): stdClass
            {
                $row->b = 2;
                return $row;
            }
        };
        $adapter = $this->createSqliteConnection()->adapter;
        $result = $adapter->runQuery($statement);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertSame($statement, $result->statement);
        $data = $result->first();
        $this->assertSame(1, $data->a);
        $this->assertSame(2, $data->b);
    }
}
