<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Collections\Map;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Event\EventManager;
use function array_key_exists;
use function array_key_first;
use function assert;
use function count;

class DatabaseManager
{
    /**
     * @var array<string, Connection>
     */
    protected array $connections = [];

    /**
     * @var array<string, Closure(ConnectionConfig): Adapter<ConnectionConfig>>
     */
    protected array $adapters = [];

    /**
     * @param EventManager $events
     * @param DatabaseConfig $config
     */
    public function __construct(
        protected readonly EventManager $events,
        public readonly DatabaseConfig $config,
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
     * @return Connection
     */
    public function useDefault(): Connection
    {
        return $this->use($this->getDefaultConnectionName());
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
     * @return string
     */
    public function getDefaultConnectionName(): string
    {
        $default = $this->config->default;
        assert($default !== null);
        return $default;
    }

    /**
     * @param string $name
     * @param ConnectionConfig $config
     * @return Connection
     */
    protected function createConnection(string $name, ConnectionConfig $config): Connection
    {
        $resolver = $this->getAdapterResolver($config->getAdapterName());
        return new Connection($name, $resolver($config), $this->events);
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
            'config' => $this->config,
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
        return $this->config->connections[$name] ?? throw new LogicException("Database: {$name} does not exist", [
            'name' => $name,
            'config' => $this->config,
        ]);
    }

    /**
     * @param string $name
     * @param Closure(ConnectionConfig): Adapter<ConnectionConfig> $deferred
     * @return $this
     */
    public function addAdapter(string $name, Closure $deferred): static
    {
        $this->adapters[$name] = $deferred(...);
        return $this;
    }

    /**
     * @param string $name
     * @return Closure(ConnectionConfig): Adapter<ConnectionConfig>
     */
    protected function getAdapterResolver(string $name): Closure
    {
        if (!array_key_exists($name, $this->adapters)) {
            $this->addAdapter($name, $this->getDefaultAdapterResolver($name));
        }
        return $this->adapters[$name];
    }

    /**
     * @param string $adapter
     * @return Closure(covariant ConnectionConfig): Adapter<ConnectionConfig>
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        return match ($adapter) {
            'mysql' => fn(MySqlConfig $cfg) => new MySqlAdapter($this->config, $cfg),
            'sqlite' => fn(SqliteConfig $cfg) => new SqliteAdapter($this->config, $cfg),
            default => throw new LogicException("No adapter resolver exists for: {$adapter}"),
        };
    }
}
