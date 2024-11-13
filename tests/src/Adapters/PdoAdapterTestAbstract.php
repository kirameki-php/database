<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Adapters\PdoAdapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\Statements\RawStatement as SchemaRawStatement;
use Kirameki\Database\Schema\Statements\SchemaResult;
use Kirameki\Time\Time;
use stdClass;
use Tests\Kirameki\Database\DatabaseTestCase;
use Tests\Kirameki\Database\Query\Builders\_Support\IntCastEnum;
use function dump;
use function rand;

class PdoAdapterTestAbstract extends DatabaseTestCase
{
    protected string $useConnection;

    /**
     * @return PdoAdapter<covariant ConnectionConfig>
     */
    protected function createAdapter(?DatabaseConfig $config = null): PdoAdapter
    {
        return match ($this->useConnection) {
            'sqlite' => $this->createSqliteAdapter($config),
            'mysql' => $this->createMySqlAdapter(null, $config),
            default => throw new NotSupportedException(),
        };
    }

    protected function createConnection(): Connection
    {
        $connection = $this->createTempConnection($this->useConnection);
        $adapter = $connection->adapter;
        $adapter->createDatabase();
        $adapter->connect();
        return $connection;
    }

    public function test_createDatabase_not_existing(): void
    {
        $adapter = $this->createAdapter();
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
    }

    public function test_dropDatabase_database_exist(): void
    {
        $adapter = $this->createAdapter();
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
    }

    public function test_dropDatabase_with_dropProtection_enabled(): void
    {
        $config = new DatabaseConfig([], dropProtection: true);
        $adapter = $this->createAdapter($config);
        $adapter->createDatabase();
        $this->expectException(DropProtectionException::class);
        $this->expectExceptionMessage('Dropping databases are prohibited by configuration.');
        $adapter->dropDatabase();
    }

    public function test_isConnected(): void
    {
        $adapter = $this->createAdapter();
        $this->assertFalse($adapter->isConnected());
        $adapter->createDatabase();
        $adapter->connect();
        $this->assertTrue($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
    }

    public function test_connect(): void
    {
        $connection = $this->createConnection();
        $this->assertTrue($connection->adapter->isConnected());
    }

    public function test_disconnect(): void
    {
        $adapter = $this->createAdapter();
        $adapter->createDatabase();
        $this->assertTrue($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
        $adapter->connect();
        $this->assertTrue($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
    }

    public function test_inTransaction(): void
    {
        $adapter = $this->createConnection()->adapter;
        $this->assertFalse($adapter->inTransaction());
        $adapter->beginTransaction();
        $this->assertTrue($adapter->inTransaction());
        $adapter->commit();
        $this->assertFalse($adapter->inTransaction());
    }

    public function test_beginTransaction(): void
    {
        $adapter = $this->createConnection()->adapter;
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test_table (id INT PRIMARY KEY)'));
        $this->assertFalse($adapter->inTransaction());
        $adapter->runQuery(new RawStatement('INSERT INTO test_table (id) VALUES (1)'));
        $adapter->beginTransaction();
        $adapter->runQuery(new RawStatement('INSERT INTO test_table (id) VALUES (2)'));
        $this->assertTrue($adapter->inTransaction());
        $this->assertSame(2, $adapter->runQuery(new RawStatement('SELECT COUNT(*) as count FROM test_table'))->first()->count);
        $adapter->rollback();
        $this->assertFalse($adapter->inTransaction());
        $this->assertSame(1, $adapter->runQuery(new RawStatement('SELECT COUNT(*) as count FROM test_table'))->first()->count);
    }

    public function test_rollback(): void
    {
        $adapter = $this->createConnection()->adapter;
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test_table (id INT PRIMARY KEY)'));
        $adapter->beginTransaction();
        $adapter->runQuery(new RawStatement('INSERT INTO test_table (id) VALUES (1)'));
        $adapter->rollback();
        $this->assertFalse($adapter->inTransaction());
        $this->assertSame(0, $adapter->runQuery(new RawStatement('SELECT COUNT(*) as count FROM test_table'))->first()->count);
    }

    public function test_runSchema_with_valid_statement(): void
    {
        $tableName = 'test_table_' . rand(1000, 9999);
        $connection = $this->createConnection();
        $adapter = $connection->adapter;
        $result = $adapter->runSchema(new SchemaRawStatement("CREATE TABLE {$tableName} (id INT PRIMARY KEY)"));
        $this->assertInstanceOf(SchemaResult::class, $result);
        $this->assertInstanceOf(SchemaRawStatement::class, $result->statement);
        $this->assertSame(["CREATE TABLE {$tableName} (id INT PRIMARY KEY)"], $result->commands);
        $this->assertTrue($connection->info()->tableExists($tableName));
    }

    public function test_runSchema_invalid_syntax(): void
    {
        $adapter = $this->createConnection()->adapter;
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage(match ($this->useConnection) {
            'mysql' => 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax;',
            'sqlite' => 'SQLSTATE[HY000]: General error: 1 near "HELLO": syntax error',
            default => throw new NotSupportedException(),
        });
        $adapter->runSchema(new SchemaRawStatement("HELLO"));
    }

    public function test_runQuery_with_valid_statement(): void
    {
        $adapter = $this->createConnection()->adapter;
        $result = $adapter->runQuery(new RawStatement('SELECT ?', [1]));
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertInstanceOf(RawStatement::class, $result->statement);
        $this->assertSame('SELECT ?', $result->template);
        $this->assertSame([1], $result->parameters);
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
        $adapter = $this->createConnection()->adapter;
        $result = $adapter->runQuery($statement);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertSame($statement, $result->statement);
        $data = $result->first();
        $this->assertSame(1, $data->a);
        $this->assertSame(2, $data->b);
    }

    public function test_runQuery_with_casts(): void
    {
        $adapter = $this->createConnection()->adapter;
        $casts = ['a' => Time::class, 'b' => IntCastEnum::class];
        $result = $adapter->runQuery(new RawStatement('SELECT ? as a, 1 as b, 2 as c', ['2024-01-01'], $casts));
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertInstanceOf(RawStatement::class, $result->statement);
        $this->assertSame('SELECT ? as a, 1 as b, 2 as c', $result->template);
        $this->assertSame(['2024-01-01'], $result->parameters);
        $data = $result->first();
        $this->assertInstanceOf(Time::class, $data->a);
        $this->assertSame(IntCastEnum::A, $data->b);
        $this->assertSame(2, $data->c);
    }

    public function test_runQuery_invalid_syntax(): void
    {
        $adapter = $this->createConnection()->adapter;
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(match ($this->useConnection) {
            'mysql' => 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax;',
            'sqlite' => 'SQLSTATE[HY000]: General error: 1 near "HELLO": syntax error',
            default => throw new NotSupportedException(),
        });
        $adapter->runQuery(new RawStatement('HELLO'));
    }

    public function test_runQueryWithCursor_with_valid_statement(): void
    {
        $adapter = $this->createConnection()->adapter;
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test_table (id INT PRIMARY KEY)'));
        $adapter->runQuery(new RawStatement('INSERT INTO test_table (id) VALUES (1), (2), (3)'));
        $result = $adapter->runQueryWithCursor(new RawStatement('SELECT * FROM test_table'));
        $this->assertInstanceOf(QueryResult::class, $result);
        foreach ($result as $index => $row) {
            $this->assertSame($index + 1, $row->id);
        }
    }

    public function test_runQueryWithCursor_with_normalize(): void
    {
        $statement = new class('SELECT 1 as a') extends RawStatement implements Normalizable {
            public function normalize(QuerySyntax $syntax, stdClass $row): stdClass
            {
                $row->b = 2;
                return $row;
            }
        };
        $adapter = $this->createConnection()->adapter;
        $result = $adapter->runQueryWithCursor($statement);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertSame($statement, $result->statement);
        $data = $result->first();
        $this->assertSame(1, $data->a);
        $this->assertSame(2, $data->b);
    }

    public function test_runQueryWithCursor_with_casts(): void
    {
        $adapter = $this->createConnection()->adapter;
        $casts = ['a' => Time::class, 'b' => IntCastEnum::class];
        $result = $adapter->runQueryWithCursor(new RawStatement('SELECT ? as a, 1 as b, 2 as c', ['2024-01-01'], $casts));
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertInstanceOf(RawStatement::class, $result->statement);
        $this->assertSame('SELECT ? as a, 1 as b, 2 as c', $result->template);
        $this->assertSame(['2024-01-01'], $result->parameters);
        $data = $result->first();
        $this->assertInstanceOf(Time::class, $data->a);
        $this->assertSame(IntCastEnum::A, $data->b);
        $this->assertSame(2, $data->c);
    }

    public function test_runQueryWithCursor_with_invalid_syntax(): void
    {
        $adapter = $this->createConnection()->adapter;
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(match ($this->useConnection) {
            'mysql' => 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax;',
            'sqlite' => 'SQLSTATE[HY000]: General error: 1 near "HELLO": syntax error',
            default => throw new NotSupportedException(),
        });
        $adapter->runQueryWithCursor(new RawStatement('HELLO'));
    }
}
