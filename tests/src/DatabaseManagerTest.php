<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Core\Exceptions\InvalidConfigException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\DatabaseManager;
use Tests\Kirameki\Database\Query\QueryTestCase;

class DatabaseManagerTest extends QueryTestCase
{
    public function test_constructor_empty(): void
    {
        $config = new DatabaseConfig([]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('At least one connection configuration is required.');
        new DatabaseManager($config);
    }

    public function test_constructor_auto_select_default(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $connection = $db->useDefault();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertSame('_', $connection->name);
    }

    public function test_constructor_multi_connection_but_no_default(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Default connection could not be resolved automatically.');
        $config = new DatabaseConfig([
            'a' => new SqliteConfig(':memory:'),
            'b' => new SqliteConfig(':memory:'),
        ]);
        new DatabaseManager($config)->default;
    }

    public function test_constructor_non_existent_default(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Default connection: "b" does not exist.');
        $config = new DatabaseConfig(['a' => new SqliteConfig(':memory:')], 'b');
        new DatabaseManager($config)->default;
    }

    public function test_default_property(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $this->assertSame('_', $db->default);
        $this->assertSame('_', $db->default);
    }

    public function test_use_sqlite_connection(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $this->assertInstanceOf(Connection::class, $db->use('_'));
    }

    public function test_use_mysql_connection(): void
    {
        $config = new DatabaseConfig(['_' => new MySqlConfig()]);
        $db = new DatabaseManager($config);
        $this->assertInstanceOf(Connection::class, $db->use('_'));
    }

    public function test_useDefault(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')], '_');
        $db = new DatabaseManager($config);
        $connection = $db->useDefault();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertSame('_', $connection->name);
    }

    public function test_purge__connection_exist(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $db->use('_');
        $this->assertTrue($db->purge('_'));
        $this->assertCount(0, $db->resolvedConnections());
    }

    public function test_purge__connection_not_exist(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Failed to purge connection: "b". No such name.');
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $db->purge('b');
    }

    public function test_purge__connection_not_connected(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $this->assertFalse($db->purge('_'));
        $this->assertCount(0, $db->resolvedConnections());
    }

    public function test_purgeAll__empty(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $this->assertTrue($db->purgeAll()->isEmpty());
    }

    public function test_purgeAll__filled(): void
    {
        $config = new DatabaseConfig([
            'a' => new SqliteConfig(':memory:'),
            'b' => new SqliteConfig(':memory:'),
        ], 'a');
        $db = new DatabaseManager($config);
        $db->use('a');
        $db->use('b');
        $this->assertCount(2, $db->resolvedConnections());
        $result = $db->purgeAll();
        $this->assertSame(['a', 'b'], $result->keys()->all());
        $this->assertCount(0, $db->resolvedConnections());
    }

    public function test_setConnectionConfig(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $db->setConnectionConfig('a', new SqliteConfig(':memory:'));
        $this->assertCount(0, $db->resolvedConnections());
        $this->assertInstanceOf(Connection::class, $db->use('a'));
        $this->assertCount(1, $db->resolvedConnections());
    }

    public function test_getConfig__valid(): void
    {
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $this->assertInstanceOf(SqliteConfig::class, $db->getConfig('_'));
    }

    public function test_getConfig__invalid_name(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Failed to get config for connection: "x". No such name.');
        $config = new DatabaseConfig(['_' => new SqliteConfig(':memory:')]);
        $db = new DatabaseManager($config);
        $this->assertInstanceOf(SqliteConfig::class, $db->getConfig('x'));
    }

    public function test_addAdapter(): void
    {
        $connectionConfig = new class(':memory:') extends SqliteConfig {
            public function getAdapterName(): string { return 'custom'; }
        };
        $config = new DatabaseConfig(['_' => $connectionConfig]);
        $db = new DatabaseManager($config);
        $adapter = new SqliteAdapter($config, $connectionConfig);
        $db->addAdapter('custom', fn(): SqliteAdapter => $adapter);
        $connection = $db->use('_');
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertSame($adapter, $connection->adapter);
    }
}
