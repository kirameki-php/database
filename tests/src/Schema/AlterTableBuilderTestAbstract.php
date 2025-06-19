<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Config\DatabaseConfig;
use Kirameki\Database\Exceptions\DropProtectionException;
use Kirameki\Database\Info\Statements\ColumnType;
use Kirameki\Database\Schema\Statements\Table\AlterTableStatement;
use RuntimeException;
use function random_int;

abstract class AlterTableBuilderTestAbstract extends SchemaTestCase
{
    public function test___clone(): void
    {
        $builder = $this->connect()->schema()->alterTable('users');
        $builder->addColumn('id')->int()->primaryKey();
        $clone = clone $builder;

        $this->assertNotSame($builder->statement, $clone->statement);
    }

    public function test_execute(): void
    {
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable('users');
        $create->id();
        $create->execute();

        $builder = $schema->alterTable('users');
        $builder->addColumn('text')->string();
        $result = $builder->execute();

        $this->assertInstanceOf(AlterTableStatement::class, $result->statement);
        $this->assertCount(1, $result->commands);
        $this->assertGreaterThan(0, $result->elapsedMs);
    }

    abstract public function test_addColumn__int(): void;

    abstract public function test_addColumn__int_with_size(): void;

    public function test_addColumn__float(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('f')->float();
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('f');
        $this->assertSame(ColumnType::Float, $info->type);
        $this->assertSame('f', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    abstract public function test_addColumn__float_with_valid_size(): void;

    abstract public function test_addColumn__float_with_invalid_size(): void;

    public function test_addColumn__decimal(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('d')->decimal(10, 2);
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('d');
        $this->assertSame(ColumnType::Decimal, $info->type);
        $this->assertSame('d', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    public function test_addColumn__bool(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('b')->bool();
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('b');
        $this->assertSame(ColumnType::Bool, $info->type);
        $this->assertSame('b', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    public function test_addColumn__timestamp(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('ts')->timestamp();
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('ts');
        $this->assertSame(ColumnType::Timestamp, $info->type);
        $this->assertSame('ts', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    public function test_addColumn__string(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('s')->string(10);
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('s');
        $this->assertSame(ColumnType::String, $info->type);
        $this->assertSame('s', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    public function test_addColumn__text(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('t')->text();
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('t');
        $this->assertSame(ColumnType::String, $info->type);
        $this->assertSame('t', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    public function test_addColumn__json(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('j')->json();
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('j');
        $this->assertSame(ColumnType::Json, $info->type);
        $this->assertSame('j', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    public function test_addColumn__uuid(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('u')->uuid();
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('u');
        $this->assertSame(ColumnType::String, $info->type);
        $this->assertSame('u', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
    }

    public function test_addColumn__without_type(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Definition type cannot be set to null.');

        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('c'); // No type specified
        $alter->execute();
    }

    abstract public function test_modifyColumn(): void;

    public function test_renameColumn(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->renameColumn('id', 'new_id');
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('new_id');
        $this->assertSame(ColumnType::Int, $info->type);
        $this->assertSame('new_id', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(1, $info->position);
        $this->assertSame(
            "ALTER TABLE \"{$table}\" RENAME COLUMN \"id\" TO \"new_id\";",
            $alter->toDdl(),
        );
    }

    public function test_dropColumn(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->string('name');
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->dropColumn('name');
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->getOrNull('name');
        $this->assertNull($info);
        $this->assertSame(
            "ALTER TABLE \"{$table}\" DROP COLUMN \"name\";",
            $alter->toDdl(),
        );
    }

    public function test_dropColumn__with_drop_protection_enabled(): void
    {
        $this->expectException(DropProtectionException::class);
        $this->expectExceptionMessage('Dropping columns is prohibited in database ');

        $databaseConfig = new DatabaseConfig([]);
        $databaseConfig->dropProtection = true;
        $adapter = match ($this->connection) {
            'mysql' => $this->createMySqlAdapter(null, $databaseConfig),
            'sqlite' => $this->createSqliteAdapter($databaseConfig),
            default => throw new RuntimeException("Unsupported driver"),
        };
        $conn = $this->createTempConnection($this->connection, $adapter);
        $builder = $conn->schema()->alterTable('users');
        $builder->dropColumn('name');
        try {
            $builder->execute();
        } finally {
            $databaseConfig->dropProtection = false; // Reset drop protection for other tests
        }
    }

    abstract public function test_addForeignKey(): void;

    abstract public function test_dropForeignKey(): void;
}
