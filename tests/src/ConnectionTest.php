<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use function dump;

class ConnectionTest extends DatabaseTestCase
{
    protected function createDummyTable(string $connection): void
    {
        $this->createTable($connection, 'Dummy', function(CreateTableBuilder $schema) {
            $schema->int('id')->primaryKey()->nullable()->autoIncrement();
            $schema->uuid('name')->nullable();
            $schema->bool('exists')->nullable();
            $schema->json('data');
            $schema->index(['name']);
        });
    }

    public function test_tableExists(): void
    {
        $this->createDummyTable('sqlite');
        dump($this->sqliteConnection()->info()->getTable('Dummy'));

        $this->createDummyTable('mysql');
        dump($this->mysqlConnection()->info()->getTable('Dummy'));

        $this->sqliteConnection()->query()->select('*')->from('Dummy')->forceIndex('Dummy_name')->execute();
    }
}
