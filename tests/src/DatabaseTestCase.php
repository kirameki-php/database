<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Collections\Arr;
use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Testing\Concerns\UsesDatabases;
use Tests\Kirameki\TestCase;

class DatabaseTestCase extends TestCase
{
    use UsesDatabases;

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
        Arr::map($builder->build(), fn($ddl) => $connection->applySchema($ddl));
    }
}
