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
            $schema->string('first', 255)->nullable();
            $schema->string('second', 255)->nullable();
            $schema->bool('exists')->nullable();
            $schema->json('data')->nullable();
//            $schema->primaryKey(['id', 'name']);
            $schema->index(['first', 'second'])->unique();
            $schema->index(['name']);
        });
    }

    public function test_tableExists(): void
    {
        $this->createDummyTable('sqlite');
        $this->sqliteConnection()->info()->getTableInfo('Dummy');

        $str = $this->sqliteConnection()->query()
            ->select('*')
            ->from('Dummy')
            ->forceIndex('Dummy_name')
            ->addTag('test', 'te\'st')
            ->execute();

        $str = $this->sqliteConnection()->query()
            ->insertInto('Dummy')
            ->value(['first' => 'test'])
            ->addTag('test', 'te\'st')
            ->execute();

        dump($str);
//
//        $this->createDummyTable('mysql');
//        dump($this->mysqlConnection()->info()->getTable('Dummy'));
//
//        $this->sqliteConnection()->query()->select('*')->from('Dummy')->forceIndex('Dummy_name')->execute();
//        $this->sqliteConnection()->query()->insertInto('Dummy')->execute();
    }
}
