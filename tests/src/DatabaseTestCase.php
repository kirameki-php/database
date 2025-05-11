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
use Kirameki\Event\Event;
use Kirameki\Event\EventManager;
use RuntimeException;
use function array_values;
use function mt_rand;

class DatabaseTestCase extends TestCase
{
    /**
     * @var array<Connection>
     */
    protected array $connections = [];

    protected ?EventManager $eventManager = null;

    /**
     * @var list<Event>
     */
    protected array $capturedEvents = [];

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
        $conn->schema()->execute($builder->statement);
    }

    /**
     * @template TConnectionConfig of ConnectionConfig
     * @template TAdapter of Adapter<TConnectionConfig>
     * @param string $driver
     * @param TAdapter|null $adapter
     * @return Connection
     */
    public function createTempConnection(string $driver, ?Adapter $adapter = null): Connection
    {
        $adapter ??= $this->resolveAdapter($driver);
        $adapter->createDatabase();
        $this->runAfterTearDown(static fn() => $adapter->dropDatabase());

        return new Connection('temp', $adapter, $this->getEventManager());
    }

    /**
     * @param string $driver
     * @return Adapter<covariant ConnectionConfig>
     */
    public function resolveAdapter(string $driver): Adapter
    {
        return match ($driver) {
            'mysql' => $this->createMySqlAdapter(),
            'sqlite' => $this->createSqliteAdapter(),
            default => throw new RuntimeException("Unsupported driver: $driver"),
        };
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

    /**
     * @param class-string<Event> $event
     * @return void
     */
    protected function captureEvents(string $event): void
    {
        $this->getEventManager()->on($event, fn ($e) => $this->capturedEvents[] = $e);
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $event
     * @return list<TEvent>
     */
    protected function getCapturedEvents(string $event): array
    {
        return array_values(
            array_filter($this->capturedEvents, static fn ($e) => $e instanceof $event),
        );
    }
}
