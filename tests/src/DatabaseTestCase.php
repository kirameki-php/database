<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Adapters\MySqlConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\Statements\Schema\CreateTableBuilder;
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

    public function createTable(string $table, callable $callback): void
    {
        $connection = $this->mysqlConnection();
        $builder = new CreateTableBuilder($connection, $table);
        $callback($builder);
        $connection->schema()->execute($builder->getStatement());
    }

    public function createTempConnection(string $driver): Connection
    {
        $name = 'test_' . mt_rand();
        $adapter = match ($driver) {
            'mysql' => new MySqlAdapter(new MySqlConfig(host: 'mysql', database: $name)),
            default => throw new RuntimeException("Unsupported driver: $driver"),
        };
        $adapter->createDatabase();
        $this->runAfterTearDown(fn() => $adapter->dropDatabase());

        return new Connection($name, $adapter, new EventManager());
    }
}
