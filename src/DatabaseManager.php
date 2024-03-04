<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Collections\Map;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Adapters\DatabaseConfig;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\MySqlConfig;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Adapters\SqliteConfig;
use Kirameki\Event\EventManager;
use LogicException;
use function array_key_exists;

class DatabaseManager
{
    /**
     * @var array<string, Connection>
     */
    protected array $connections = [];

    /**
     * @var array<string, Closure(DatabaseConfig): DatabaseAdapter>
     */
    protected array $adapters = [];

    /**
     * @param EventManager $events
     * @param iterable<string, DatabaseConfig> $configs
     */
    public function __construct(
        protected EventManager $events,
        protected iterable $configs,
    )
    {
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function using(string $name): Connection
    {
        return $this->connections[$name] ??= $this->createConnection($name, $this->getConfig($name));
    }

    /**
     * @param string $name
     * @return $this
     */
    public function purge(string $name): static
    {
        unset($this->connections[$name]);
        return $this;
    }

    /**
     * @return $this
     */
    public function purgeAll(): static
    {
        $this->connections = [];
        return $this;
    }

    /**
     * @param Connection $connection
     * @return $this
     */
    public function addConnection(Connection $connection): static
    {
        $this->connections[$connection->name] = $connection;
        return $this;
    }

    /**
     * @param string $name
     * @param Closure(DatabaseConfig): DatabaseAdapter $deferred
     * @return $this
     */
    public function addAdapter(string $name, Closure $deferred): static
    {
        $this->adapters[$name] = $deferred(...);
        return $this;
    }

    /**
     * @param string $name
     * @param DatabaseConfig $config
     * @return Connection
     */
    protected function createConnection(string $name, DatabaseConfig $config): Connection
    {
        $adapter = ($this->getAdapterResolver($config))($config);
        return new Connection($name, $adapter, $this->events);
    }

    /**
     * @return Map<string, Connection>
     */
    public function resolvedConnections(): Map
    {
        return new Map($this->connections);
    }

    /**
     * @param string $name
     * @return DatabaseConfig
     */
    public function getConfig(string $name): DatabaseConfig
    {
        return $this->configs[$name] ?? throw new LogicException("Database config: $name does not exist");
    }

    /**
     * @param DatabaseConfig $config
     * @return Closure(DatabaseConfig): DatabaseAdapter
     */
    protected function getAdapterResolver(DatabaseConfig $config): Closure
    {
        $name = $config->getAdapterName();
        if (!array_key_exists($name, $this->adapters)) {
            $this->addAdapter($name, $this->getDefaultAdapterResolver($name));
        }
        return $this->adapters[$name];
    }

    /**
     * @param string $adapter
     * @return Closure(DatabaseConfig): DatabaseAdapter
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        return match ($adapter) {
            'mysql' => static fn(MySqlConfig $cfg) => new MySqlAdapter($cfg),
            'sqlite' => static fn(SqliteConfig $cfg) => new SqliteAdapter($cfg),
            default => throw new LogicException("No adapter resolver exists for: {$adapter}"),
        };
    }
}
