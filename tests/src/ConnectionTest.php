<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;

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
        $this->createTable('User', function(CreateTableBuilder $schema) {
            $schema->int('id')->primaryKey()->notNull();
        });

        $this->createTable('UserItem', function(CreateTableBuilder $schema) {
            $schema->int('id')->primaryKey()->notNull();
            $schema->int('userId')->notNull();
        });

        $this->mysqlConnection()->insertInto('User')->value(['id' => 1])->execute();
        $this->mysqlConnection()->insertInto('UserItem')->value(['id' => 100, 'userId' => 1])->execute();

        //dump($this->mysqlConnection()->query('SELECT * FROM User INNER JOIN UserItem AS ui ON User.id = ui.userId;'));
    }

    public function test_tableExists(): void
    {
        $this->createDummyTable();

        self::assertTrue($this->mysqlConnection()->tableExists('Dummy'));
    }

    public function test_cursor(): void
    {
        $this->createDummyTable();
        $conn = $this->mysqlConnection();
        $conn->insertInto('Dummy')->value(['id' => 'test'])->execute();

        $count = 0;
        foreach ($this->mysqlConnection()->cursor('SELECT * FROM Dummy') as $value) {
            $this->assertEquals(['id' => 'test'], $value);
            $count++;
        }
        $this->assertEquals(1, $count);
    }
}
