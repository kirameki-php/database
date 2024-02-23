<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Migration;

use Kirameki\Database\Migration\MigrationManager;

class MigrationManagerTest extends MySql_MigrationTestCase
{
    public function test_migrate_up(): void
    {
        $manager = new MigrationManager(__DIR__.'/files');
        $manager->up();

        $connection = db()->using('migration_test');

        self::assertTrue($connection->tableExists('User'));
    }
}
