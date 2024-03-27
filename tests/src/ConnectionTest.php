<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use function dump;

class ConnectionTest extends DatabaseTestCase
{
    protected function createDummyTable(): void
    {
        $this->createTable('Dummy', function(CreateTableBuilder $schema) {
            $schema->int('id')->primaryKey()->notNull()->autoIncrement();
        });
    }

    /**
     * TODO delete me
     */
    public function testTableExists2(): void
    {
        Abc::test('a', 'b', t: 'b', c: 'c');
    }

    public function test_tableExists(): void
    {
        $this->createDummyTable();
        dump($this->sqliteConnection()->info()->getTable('Dummy'));
    }
}
