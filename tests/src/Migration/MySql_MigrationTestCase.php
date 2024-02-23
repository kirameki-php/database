<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Migration;

use Kirameki\Core\Config;
use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Connection;
use Tests\Kirameki\Database\DatabaseTestCase;

class MySql_MigrationTestCase extends DatabaseTestCase
{
    /**
     * @before
     */
    protected function setUpDatabase(): void
    {
        $adapter = $this->migrationConnection()->getAdapter();
        $adapter->dropDatabase();
        $adapter->createDatabase();
    }

    /**
     * @after
     */
    protected function tearDownDatabase(): void
    {
        $this->migrationConnection()->getAdapter()->dropDatabase();
    }

    protected function migrationConnection(): Connection
    {
        $adapter = new MySqlAdapter(new Config(['host' => 'mysql', 'database' => 'migration_test']));
        $connection = new Connection('migration_test', $adapter, event());
        db()->addConnection($connection);
        return $connection;
    }
}
