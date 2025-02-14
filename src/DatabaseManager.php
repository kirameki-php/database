<?php declare(strict_types=1);

namespace Kirameki\Database;

use Closure;
use Kirameki\Collections\Map;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidConfigException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Adapters\Adapter;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Config\ConnectionConfig;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Config\SqliteConfig;
use Kirameki\Database\Query\TypeCastRegistry;
use Kirameki\Event\EventEmitter;
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
     * @var array<string, Closure(ConnectionConfig): Adapter<ConnectionConfig>>
     */
    protected array $adapters = [];

    /**
     * @var string
     */
    public string $default {
        get => $this->default ??= $this->resolveDefaultConnectionName();
    }

    protected TypeCastRegistry $casters {
        get => $this->casters ??= new TypeCastRegistry();
    }

    /**
     * @param DatabaseConfig $config
     * @param EventEmitter|null $events
     */
    public function __construct(
        public readonly DatabaseConfig $config,
        protected readonly ?EventEmitter $events = null,
    )
    {
        if ($config->connections === []) {
            throw new InvalidConfigException('At least one connection configuration is required.', [
                'config' => $config,
            ]);
        }
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function use(string $name): Connection
    {
        return $this->connections[$name] ??= $this->createConnection($name);
    }

    /**
     * @return Connection
     */
    public function useDefault(): Connection
    {
        return $this->use($this->default);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function purge(string $name): bool
    {
        $connection = $this->connections[$name] ?? null;
        if ($connection !== null) {
            $connection->disconnectIfConnected();
            unset($this->connections[$name]);
            return true;
        }

        if (!array_key_exists($name, $this->config->connections)) {
            throw new LogicException("Failed to purge connection: \"{$name}\". No such name.", [
                'name' => $name,
                'config' => $this->config,
            ]);
        }

        return false;
    }

    /**
     * @return Map<string, Connection>
     */
    public function purgeAll(): Map
    {
        $connections = $this->resolvedConnections();
        $connections->keys()->each($this->purge(...));
        return $connections;
    }

    /**
     * @param string $name
     * @return Connection
     */
    protected function createConnection(string $name): Connection
    {
        $config = $this->getConfig($name);
        $resolver = $this->getAdapterResolver($config->getAdapterName());
        return new Connection($name, $resolver($config), $this->events);
    }

    /**
     * @param string $name
     * @param ConnectionConfig $config
     * @return $this
     */
    public function setConnectionConfig(string $name, ConnectionConfig $config): static
    {
        $this->config->connections[$name] = $config;
        return $this;
    }

    /**
     * @return string
     */
    protected function resolveDefaultConnectionName(): string
    {
        $default = $this->config->default;
        if ($default !== null) {
            return $this->ensureDefaultExists($default);
        }

        $connections = $this->config->connections;
        $primaries = Arr::filter($connections, static fn(ConnectionConfig $c) => !$c->isReadOnly());
        if (count($primaries) === 1) {
            $default = array_key_first($primaries);
            return $this->ensureDefaultExists($default);
        }

        throw new InvalidConfigException('Default connection could not be resolved automatically.', [
            'config' => $this->config,
        ]);
    }

    protected function ensureDefaultExists(string $default): string
    {
        if (!array_key_exists($default, $this->config->connections)) {
            throw new InvalidConfigException("Default connection: \"{$default}\" does not exist.", [
                'config' => $this->config,
                'default' => $default,
            ]);
        }
        return $default;
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
        return $this->config->connections[$name] ?? throw new LogicException("Failed to get config for connection: \"{$name}\". No such name.", [
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
        $this->adapters[$name] = $deferred;
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
     * @return Closure(ConnectionConfig): Adapter<ConnectionConfig>
     */
    protected function getDefaultAdapterResolver(string $adapter): Closure
    {
        return match ($adapter) {
            'mysql' => $this->getMySqlAdapter(...),
            'sqlite' => $this->getSqliteAdapter(...),
            default => throw new LogicException("No adapter resolver exists for: {$adapter}"),
        };
    }

    /**
     * @param MySqlConfig $connectionConfig
     * @return MySqlAdapter
     */
    protected function getMySqlAdapter(ConnectionConfig $connectionConfig): MySqlAdapter
    {
        return new MySqlAdapter($this->config, $connectionConfig, $this->casters);
    }

    /**
     * @param SqliteConfig $connectionConfig
     * @return SqliteAdapter
     */
    protected function getSqliteAdapter(SqliteConfig $connectionConfig): SqliteAdapter
    {
        return new SqliteAdapter($this->config, $connectionConfig, $this->casters);
    }
}
