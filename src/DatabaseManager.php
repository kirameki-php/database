<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Collections\Map;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\DatabaseAdapter;
use Kirameki\Database\Adapters\ConnectionConfig;
use Kirameki\Database\Adapters\DatabaseConfig;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\MySqlConfig;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Adapters\SqliteConfig;
use Kirameki\Event\EventManager;
use function array_key_exists;
use function array_key_first;
use function count;

class DatabaseManager
{
    /**
     * @var array<string, Connection>
     */
    protected array $connections = [];

    /**
     * @var array<string, Closure(ConnectionConfig): DatabaseAdapter>
     */
    protected array $adapters = [];

    /**
     * @param EventManager $events
     * @param DatabaseConfig $config
     */
    public function __construct(
        protected readonly EventManager $events,
        protected DatabaseConfig $config,
    )
    {
        $config->default ??= $this->resolveDefaultConnectionName();
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function use(string $name): Connection
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
     * @param string $name
     * @param Closure(ConnectionConfig): DatabaseAdapter $deferred
     * @return $this
     */
    public function addAdapter(string $name, Closure $deferred): static
    {
        $this->adapters[$name] = $deferred(...);
        return $this;
    }

    /**
     * @param string $name
     * @param ConnectionConfig $config
     * @return Connection
     */
    protected function createConnection(string $name, ConnectionConfig $config): Connection
    {
        $adapter = ($this->getAdapterResolver($config))($config);
        return new Connection($name, $adapter, $this->events);
    }

    /**
     * @return string
     */
    protected function resolveDefaultConnectionName(): string
    {
        $connections = $this->config->connections;
        $primaries = Arr::filter($connections, static fn(ConnectionConfig $c) => !$c->isReplica());
        if (count($primaries) === 1) {
            return array_key_first($primaries);
        }
        throw new LogicException('No default connection could be resolved', [
            'connections' => $connections,
        ]);
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
     * @return ConnectionConfig
     */
    public function getConfig(string $name): ConnectionConfig
    {
        return $this->config->connections[$name] ?? throw new LogicException("Database config: $name does not exist", [
            'name' => $name,
            'config' => $this->config,
        ]);
    }

    /**
     * @param ConnectionConfig $config
     * @return Closure(ConnectionConfig): DatabaseAdapter
     */
    protected function getAdapterResolver(ConnectionConfig $config): Closure
    {
        $name = $config->getAdapterName();
        if (!array_key_exists($name, $this->adapters)) {
            $this->addAdapter($name, $this->getDefaultAdapterResolver($name));
        }
        return $this->adapters[$name];
    }

    /**
     * @param string $adapter
     * @return Closure(covariant ConnectionConfig): DatabaseAdapter
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
