<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

use Kirameki\Database\Exceptions\SchemaException;

class MySqlAdapterTest extends PdoAdapterTestAbstract
{
    protected string $useConnection = 'mysql';

    public function test_createDatabase_with_ifNotExist_disabled(): void
    {
        $adapter = $this->createAdapter();
        $adapter->dropDatabase();
        $this->assertFalse($adapter->databaseExists());
        $adapter->createDatabase(false);
        $this->assertTrue($adapter->databaseExists());

        $database = $adapter->connectionConfig->getTableSchema();
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("SQLSTATE[HY000]: General error: 1007 Can't create database '{$database}'; database exists");
        $adapter->createDatabase(false);
        $this->assertTrue($adapter->databaseExists());
    }

    public function test_dropDatabase_database_ifExists_disabled(): void
    {
        $adapter = $this->createAdapter();
        $adapter->createDatabase();
        $this->assertTrue($adapter->databaseExists());
        $adapter->dropDatabase(false);

        $database = $adapter->connectionConfig->getTableSchema();
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("General error: 1008 Can't drop database '{$database}'; database doesn't exist");
        $adapter->dropDatabase(false);
    }

}
