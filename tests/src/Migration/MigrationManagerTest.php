<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Migration;

use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Config\MigrationConfig;
use Kirameki\Database\Config\MySqlConfig;
use Kirameki\Database\DatabaseManager;
use Kirameki\Database\Migration\MigrationManager;
use Kirameki\Database\Migration\MigrationRepository;
use Kirameki\Database\Migration\MigrationScanner;

class MigrationManagerTest extends MySql_MigrationTestCase
{
    public function test_migrate_up(): void
    {
        $db = new DatabaseManager(new DatabaseConfig([
            'migration_test' => new MySqlConfig('mysql', 3306),
        ]));
        $migrationRepository = new MigrationRepository($db, new MigrationConfig());
        $migrationScanner = new MigrationScanner($db, [__DIR__.'/files']);
        $manager = new MigrationManager($db, $migrationRepository, $migrationScanner);
        $manager->forward();

        $connection = $db->use('migration_test');

        $this->assertTrue($connection->info()->tableExists('User'));
    }
}
