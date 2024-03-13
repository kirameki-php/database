<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Database\Statements\Schema\CreateTableBuilder;

class ConnectionTest extends DatabaseTestCase
{
    protected function createDummyTable(): void
    {
        $this->createTable('Dummy', function(CreateTableBuilder $schema) {
            $schema->uuid('id')->primaryKey()->notNull();
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

        self::assertTrue($this->mysqlConnection()->schema()->tableExists('Dummy'));
    }
}
