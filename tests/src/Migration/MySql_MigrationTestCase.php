<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Migration;

use Kirameki\Database\Adapters\MySqlAdapter;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\Connection;
use Kirameki\Database\Query\TypeCastRegistry;
use Kirameki\Event\EventManager;
use Tests\Kirameki\Database\DatabaseTestCase;

class MySql_MigrationTestCase extends DatabaseTestCase
{
    /**
     * @before
     */
    protected function setUpDatabase(): void
    {
        $adapter = $this->migrationConnection()->adapter;
        $adapter->createDatabase();
    }

    /**
     * @after
     */
    protected function tearDownDatabase(): void
    {
        $this->migrationConnection()->adapter->dropDatabase();
    }

    protected function migrationConnection(): Connection
    {
        $events = new EventManager();
        $dbConfig = new DatabaseConfig([]);
        $casters = new TypeCastRegistry();
        $adapter = new MySqlAdapter($dbConfig, new MySqlConfig('mysql'), $casters);
        return new Connection('migration_test', $adapter, $events);
    }
}
