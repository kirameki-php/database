<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Exceptions\NotSupportedException;
use Kirameki\Database\Adapters\PdoAdapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\DatabaseConnection;
use Kirameki\Database\Exceptions\ConnectionException;
use Kirameki\Database\Exceptions\DatabaseExistsException;
use Kirameki\Database\Exceptions\DatabaseNotFoundException;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Exceptions\QueryException;
use Kirameki\Database\Exceptions\SchemaException;
use Kirameki\Database\Exceptions\TransactionException;
use Kirameki\Database\Query\QueryResult;
use Kirameki\Database\Query\Statements\Normalizable;
use Kirameki\Database\Query\Statements\RawStatement;
use Kirameki\Database\Query\Syntax\QuerySyntax;
use Kirameki\Database\Schema\SchemaResult;
use Kirameki\Database\Schema\Statements\RawStatement as SchemaRawStatement;
use Kirameki\Time\Time;
use stdClass;
use Tests\Kirameki\Database\DatabaseTestCase;
use Tests\Kirameki\Database\Query\Statements\_Support\IntCastEnum;
use function dump;
use function rand;

abstract class PdoAdapterTestAbstract extends DatabaseTestCase
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

    protected function connect(): DatabaseConnection
    {
        return $this->createTempConnection($this->useConnection);
    }

    public function test_createDatabase_not_existing(): void
    {
        $adapter = $this->createAdapter();
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
    }

    public function test_createDatabase_existing(): void
    {
        $adapter = $this->createAdapter();
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
    }

    public function test_createDatabase_with_ifNotExist_disabled(): void
    {
        $adapter = $this->createAdapter();
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
        $adapter->createDatabase(false);
        $this->assertTrue($adapter->databaseExists());

        $database = $adapter->connectionConfig->getDatabaseName();
        $this->expectException(DatabaseExistsException::class);
        $this->expectExceptionMessage("'{$database}' already exists.");
        $adapter->createDatabase(false);
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

    public function test_dropDatabase_database_ifExists_disabled(): void
    {
        $adapter = $this->createAdapter();
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
        $adapter->dropDatabase(false);

        $database = $adapter->connectionConfig->getDatabaseName();
        $this->expectException(DatabaseNotFoundException::class);
        $this->expectExceptionMessage("'{$database}' does not exist.");
        $adapter->dropDatabase(false);
    }

    public function test_dropDatabase_with_dropProtection_enabled(): void
    {
        $config = new DatabaseConfig([], dropProtection: true);
        $adapter = $this->createAdapter($config);
        $adapter->createDatabase();
        $this->expectException(DropProtectionException::class);
        $this->expectExceptionMessage("Dropping database '{$adapter->connectionConfig->getDatabaseName()}' is prohibited.");
        $adapter->dropDatabase();
    }

    public function test_isConnected(): void
    {
        $adapter = $this->createAdapter();
        $this->assertFalse($adapter->isConnected());
        $adapter->createDatabase();
        $this->assertTrue($adapter->isConnected());
        $adapter->disconnect();
        $this->assertFalse($adapter->isConnected());
    }

    public function test_connect(): void
    {
        $connection = $this->connect();
        $this->assertTrue($connection->adapter->isConnected());
    }

    public function test_connecting_twice(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Already connected.');
        $adapter = $this->connect()->adapter;
        $adapter->connect();
        $adapter->connect();
    }

    abstract public function test_connect_as_readOnly(): void;

    abstract public function test_connect__failure_throws_ConnectionException(): void;

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
        $adapter = $this->connect()->adapter;
        $this->assertFalse($adapter->inTransaction());
        $adapter->beginTransaction();
        $this->assertTrue($adapter->inTransaction());
        $adapter->commit();
        $this->assertFalse($adapter->inTransaction());
    }

    public function test_beginTransaction(): void
    {
        $adapter = $this->connect()->adapter;
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test (id INT PRIMARY KEY)'));
        $this->assertFalse($adapter->inTransaction());
        $adapter->runQuery(new RawStatement('INSERT INTO test (id) VALUES (1)'));
        $adapter->beginTransaction();
        $adapter->runQuery(new RawStatement('INSERT INTO test (id) VALUES (2)'));
        $this->assertTrue($adapter->inTransaction());
        $this->assertSame(2, $adapter->runQuery(new RawStatement('SELECT COUNT(*) as count FROM test'))->first()->count);
        $adapter->rollback();
        $this->assertFalse($adapter->inTransaction());
        $this->assertSame(1, $adapter->runQuery(new RawStatement('SELECT COUNT(*) as count FROM test'))->first()->count);
    }

    abstract public function test_beginTransaction_with_isolation_level(): void;

    public function test_rollback(): void
    {
        $adapter = $this->connect()->adapter;
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test (id INT PRIMARY KEY)'));
        $adapter->beginTransaction();
        $adapter->runQuery(new RawStatement('INSERT INTO test (id) VALUES (1)'));
        $adapter->rollback();
        $this->assertFalse($adapter->inTransaction());
        $this->assertSame(0, $adapter->runQuery(new RawStatement('SELECT COUNT(*) as count FROM test'))->first()->count);
    }

    public function test_beginTransaction__failure_throws_TransactionException(): void
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('There is already an active transaction');

        $adapter = $this->createMySqlAdapter();

        $conn = $this->createTempConnection('mysql', $adapter);
        $table = $conn->schema()->createTable('t');
        $table->id();
        $table->string('name', 1)->nullable();
        $table->execute();

        $adapter->beginTransaction();
        $adapter->beginTransaction();
    }

    public function test_runSchema_with_valid_statement(): void
    {
        $tableName = 'test__' . rand(1000, 9999);
        $connection = $this->connect();
        $adapter = $connection->adapter;
        $result = $adapter->runSchema(new SchemaRawStatement("CREATE TABLE {$tableName} (id INT PRIMARY KEY)"));
        $this->assertInstanceOf(SchemaResult::class, $result);
        $this->assertInstanceOf(SchemaRawStatement::class, $result->statement);
        $this->assertSame(["CREATE TABLE {$tableName} (id INT PRIMARY KEY)"], $result->commands);
        $this->assertTrue($connection->info()->tableExists($tableName));
    }

    public function test_runSchema_invalid_syntax(): void
    {
        $adapter = $this->connect()->adapter;
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
        $adapter = $this->connect()->adapter;
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
        $adapter = $this->connect()->adapter;
        $result = $adapter->runQuery($statement);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertSame($statement, $result->statement);
        $data = $result->first();
        $this->assertSame(1, $data->a);
        $this->assertSame(2, $data->b);
    }

    public function test_runQuery_with_casts(): void
    {
        $adapter = $this->connect()->adapter;
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
        $adapter = $this->connect()->adapter;
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
        $adapter = $this->connect()->adapter;
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test (id INT PRIMARY KEY)'));
        $adapter->runQuery(new RawStatement('INSERT INTO test (id) VALUES (1), (2), (3)'));
        $result = $adapter->runQueryWithCursor(new RawStatement('SELECT * FROM test'));
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
        $adapter = $this->connect()->adapter;
        $result = $adapter->runQueryWithCursor($statement);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertSame($statement, $result->statement);
        $data = $result->first();
        $this->assertSame(1, $data->a);
        $this->assertSame(2, $data->b);
    }

    public function test_runQueryWithCursor_with_casts(): void
    {
        $adapter = $this->connect()->adapter;
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
        $adapter = $this->connect()->adapter;
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(match ($this->useConnection) {
            'mysql' => 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax;',
            'sqlite' => 'SQLSTATE[HY000]: General error: 1 near "HELLO": syntax error',
            default => throw new NotSupportedException(),
        });
        $adapter->runQueryWithCursor(new RawStatement('HELLO'));
    }

    public function test_explainQuery_with_valid_query(): void
    {
        $adapter = $this->connect()->adapter;
        $adapter->runSchema(new SchemaRawStatement('CREATE TABLE test (id INT PRIMARY KEY)'));
        $adapter->runQuery(new RawStatement('INSERT INTO test (id) VALUES (1), (2), (3)'));
        $result = $adapter->explainQuery(new RawStatement('SELECT * FROM test'));
        $this->assertSame("EXPLAIN SELECT * FROM test", $result->template);
        $this->assertTrue($result->isNotEmpty());
    }

    public function test_explainQuery_with_invalid_syntax(): void
    {
        $adapter = $this->connect()->adapter;
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage(match ($this->useConnection) {
            'mysql' => 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax;',
            'sqlite' => 'SQLSTATE[HY000]: General error: 1 unrecognized token: "!"',
            default => throw new NotSupportedException(),
        });
        $adapter->explainQuery(new RawStatement('!'));
    }
}
