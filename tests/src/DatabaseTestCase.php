<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\Table\CreateTableBuilder;
use Kirameki\Event\EventManager;
use RuntimeException;
use function mt_rand;

class DatabaseTestCase extends TestCase
{
    /**
     * @var array<Connection>
     */
    protected array $connections = [];

    protected ?EventManager $eventManager = null;

    public function connection(string $driver): Connection
    {
        return $this->connections[$driver] ??= $this->createTempConnection($driver);
    }

    public function mysqlConnection(): Connection
    {
        return $this->connections['mysql'] ??= $this->createTempConnection('mysql');
    }

    public function sqliteConnection(): Connection
    {
        return $this->connections['sqlite'] ??= $this->createTempConnection('sqlite');
    }

    public function createTable(string $connection, string $table, callable $callback): void
    {
        $conn = $this->connections[$connection] ??= $this->createTempConnection($connection);
        $builder = new CreateTableBuilder($conn->schema(), $table, false);
        $callback($builder);
        $conn->schema()->execute($builder->getStatement());
    }

    /**
     * @template TConnectionConfig of ConnectionConfig
     * @template TAdapter of Adapter<TConnectionConfig>
     * @param TAdapter|null $adapter
     */
    public function createTempConnection(string $driver, ?Adapter $adapter = null): Connection
    {
        $adapter ??= match ($driver) {
            'mysql' => $this->createMySqlAdapter($adapter),
            'sqlite' => $this->createSqliteAdapter(),
            default => throw new RuntimeException("Unsupported driver: $driver"),
        };
        $adapter->createDatabase();
        $this->runAfterTearDown(static fn() => $adapter->dropDatabase());

        return new Connection('temp', $adapter, $this->getEventManager());
    }

    public function createMySqlAdapter(
        ?string $name = null,
        ?DatabaseConfig $config = null,
        ?MySqlConfig $connectionConfig = null,
    ): MySqlAdapter
    {
        $name ??= 'test_' . mt_rand();
        $config ??= new DatabaseConfig([]);
        $connectionConfig ??= new MySqlConfig(host: 'mysql', database: $name);
        return new MySqlAdapter($config, $connectionConfig);
    }

    public function createSqliteAdapter(
        ?DatabaseConfig $config = null,
        ?SqliteConfig $connectionConfig = null,
    ): SqliteAdapter
    {
        $config ??= new DatabaseConfig([]);
        $connectionConfig ??= new SqliteConfig(':memory:');
        return new SqliteAdapter($config, $connectionConfig);
    }

    protected function getEventManager(): EventManager
    {
        return $this->eventManager ??= new EventManager();
    }
}
