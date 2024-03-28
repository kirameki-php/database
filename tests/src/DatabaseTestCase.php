<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\MySqlConfig;
use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Adapters\SqliteConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Kirameki\Event\EventManager;
use RuntimeException;
use function mt_rand;

class DatabaseTestCase extends TestCase
{
    /**
     * @var array<Connection>
     */
    protected array $connections = [];

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
        $builder = new CreateTableBuilder($conn->adapter->getSchemaSyntax(), $table);
        $callback($builder);
        $conn->schema()->execute($builder->getStatement());
    }

    public function createTempConnection(string $driver): Connection
    {
        $name = 'test_' . mt_rand();
        $adapter = match ($driver) {
            'mysql' => new MySqlAdapter(new MySqlConfig(host: 'mysql', database: $name)),
            'sqlite' => new SqliteAdapter(new SqliteConfig(':memory:')),
            default => throw new RuntimeException("Unsupported driver: $driver"),
        };
        $adapter->createDatabase();
        $this->runAfterTearDown(fn() => $adapter->dropDatabase());

        return new Connection($name, $adapter, new EventManager());
    }
}
