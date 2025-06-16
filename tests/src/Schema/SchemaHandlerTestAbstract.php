<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Exceptions\DropProtectionException;
use Random\Engine\Secure;
use Random\Randomizer;
use RuntimeException;

abstract class SchemaHandlerTestAbstract extends SchemaTestCase
{
    public function test_randomizer_property(): void
    {
        $connection = $this->connect();
        $handler = $connection->schema();
        $this->assertInstanceOf(Randomizer::class, $handler->randomizer);
        $this->assertInstanceOf(Secure::class, $handler->randomizer->engine);
    }

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

    abstract public function test_createTemporaryTable(): void;

    abstract public function test_alterTable(): void;

    abstract public function test_renameTable(): void;

    abstract public function test_renameTables(): void;

    public function test_dropTable(): void
    {
        $handler = $this->connect()->schema();

        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->execute();

        $drop = $handler->dropTable('temp');
        $drop->execute();

        $this->assertSame('DROP TABLE "temp";', $drop->toDdl());
    }

    public function test_dropTable__with_drop_protection(): void
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

        try {
            $this->expectException(DropProtectionException::class);
            $this->expectExceptionMessage("Dropping tables are prohibited.");
            $handler->dropTable('asdf')->execute();
        } finally {
            $databaseConfig->dropProtection = false;
        }
    }

    public function test_createIndex__with_auto_name(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $index = $handler->createIndex('temp', ['name']);
        $index->execute();

        $this->assertSame('CREATE INDEX "idx_temp_name" ON "temp" ("name" ASC);', $index->toDdl());
    }

    public function test_createIndex__with_name(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $index = $handler->createIndex('temp', ['name'])->name('idx_t1');
        $index->execute();

        $this->assertSame('CREATE INDEX "idx_t1" ON "temp" ("name" ASC);', $index->toDdl());
    }

    public function test_createIndex__invalid_columns_format(): void
    {
        $this->expectException(UnreachableException::class);
        $this->expectExceptionMessage('Invalid index column definition format.');

        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $handler->createIndex('temp', ['name' => 'asc'])->execute();
    }

    public function test_createIndex__empty_columns(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least 1 column needs to be defined to create an index.');

        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $handler->createIndex('temp', [])->execute();
    }

    public function test_createUniqueIndex__with_auto_name(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $index = $handler->createUniqueIndex('temp', ['name']);
        $index->execute();

        $this->assertSame('CREATE UNIQUE INDEX "idx_temp_name" ON "temp" ("name" ASC);', $index->toDdl());
    }

    public function test_createUniqueIndex__with_name(): void
    {
        $handler = $this->connect()->schema();
        $table = $handler->createTable('temp');
        $table->int('id')->nullable()->primaryKey();
        $table->string('name');
        $table->execute();

        $index = $handler->createUniqueIndex('temp', ['name'])->name('idx_t1');
        $index->execute();

        $this->assertSame('CREATE UNIQUE INDEX "idx_t1" ON "temp" ("name" ASC);', $index->toDdl());
    }

    abstract public function test_dropIndexByName(): void;

    abstract public function test_dropIndexByColumns(): void;

    public function test_dropIndexByColumns__with_empty_columns(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Name or column(s) are required to drop an index.');

        $handler = $this->connect()->schema();
        $handler->dropIndexByColumns('temp', [])->execute();
    }
}
