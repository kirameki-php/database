<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Schema\Statements\RawStatement;
use RuntimeException;
use function dump;

abstract class SchemaHandlerTestAbstract extends SchemaTestCase
{
    public function test_truncate(): void
    {
        $connection = $this->connect();
        $handler = $connection->schema();
        $tableName = __FUNCTION__;

        $table = $handler->createTable($tableName);
        $table->int('id')->autoIncrement()->primaryKey();
        $table->string('name');
        $table->execute();

        $connection->query()->insertInto($tableName)->values([
            ['name' => 'Alice'],
        ])->execute();

        $query = $connection->query()->select()->from($tableName);
        $this->assertCount(1, $query->execute());
        $handler->truncate($tableName);
        $this->assertCount(0, $query->execute());
    }

    public function test_truncate_with_drop_protection(): void
    {
        $databaseConfig = new DatabaseConfig([]);
        $databaseConfig->dropProtection = true;
        $adapter = match ($this->connection) {
            'mysql' => $this->createMySqlAdapter(null, $databaseConfig),
            'sqlite' => $this->createSqliteAdapter($databaseConfig),
            default => throw new RuntimeException("Unsupported driver"),
        };
        $connection = $this->createTempConnection($this->connection, $adapter);
        $handler = $connection->schema();
        $table = $handler->createTable('asdf');
        $table->int('id')->autoIncrement()->primaryKey();
        $table->execute();

        $errorThrown = false;
        try {
            $handler->truncate('asdf');
        } catch (DropProtectionException $e) {
            $this->assertStringStartsWith("TRUNCATE is prohibited in database ", $e->getMessage());
            $errorThrown = true;
        } finally {
            $databaseConfig->dropProtection = false;
        }
        $this->assertTrue($errorThrown, "Expected DropProtectionException to be thrown");
    }
}
