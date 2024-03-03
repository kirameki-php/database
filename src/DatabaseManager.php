<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Collections\Map;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Configs\DatabaseConfig;
use Kirameki\Event\EventManager;
use LogicException;

class DatabaseManager
{
    /**
     * @var array<string, Connection>
     */
    protected array $connections = [];

    /**
     * @var array<string, Closure>
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
        $this->connections[$connection->getName()] = $connection;
        return $this;
    }

    /**
     * @template TConfig of DatabaseConfig
     * @param string $name
     * @param Closure(TConfig): Adapter<TConfig> $deferred
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
        $adapterResolver = $this->getAdapterResolver($config->adapter);
        $adapter = $adapterResolver($config);
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
     * @param string $name
     * @return Closure(DatabaseConfig): Adapter
     */
    protected function getAdapterResolver(string $name): Closure
    {
        if (!isset($this->adapters[$name])) {
            $this->addAdapter($name, $this->getDefaultAdapterResolver($name));
        }
        return $this->adapters[$name];
    }

    /**
     * @param string $adapter
     * @return Closure(DatabaseConfig): Adapter
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        return match ($adapter) {
            'mysql' => static fn(DatabaseConfig $config) => new MySqlAdapter($config),
            'sqlite' => static fn(DatabaseConfig $config) => new SqliteAdapter($config),
            default => throw new LogicException("Adapter: $adapter does not exist"),
        };
    }
}
