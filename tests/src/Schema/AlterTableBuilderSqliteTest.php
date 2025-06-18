<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Info\Statements\ColumnType;
use function random_int;

class AlterTableBuilderSqliteTest extends AlterTableBuilderTestAbstract
{
    protected string $connection = 'sqlite';

    public function test_addColumn__int(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('i')->int();
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('i');
        $this->assertSame(ColumnType::Int, $info->type);
        $this->assertSame('i', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
        $this->assertSame(
            "ALTER TABLE \"{$table}\" ADD COLUMN \"i\" INTEGER NOT NULL;",
            $alter->toDdl(),
        );
    }

    public function test_addColumn__int_with_size(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->int('id')->primaryKey();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('i')->int(2);
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('i');
        $this->assertSame(ColumnType::Int, $info->type);
        $this->assertSame('i', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
        $this->assertSame(
            "ALTER TABLE \"{$table}\" ADD COLUMN \"i\" INTEGER CHECK (\"i\" BETWEEN -65536 AND 65535) NOT NULL;",
            $alter->toDdl(),
        );
    }

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
        $this->assertSame(
            "ALTER TABLE \"{$table}\" ADD COLUMN \"f\" REAL NOT NULL;",
            $alter->toDdl(),
        );
    }

    public function test_addColumn__float_with_valid_size(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('f')->float(8);
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('f');
        $this->assertSame(ColumnType::Float, $info->type);
        $this->assertSame('f', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
        $this->assertSame(
            "ALTER TABLE \"{$table}\" ADD COLUMN \"f\" REAL NOT NULL;",
            $alter->toDdl(),
        );
    }

    public function test_addColumn__float_with_invalid_size(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('"f" has invalid float size: 4. Sqlite only supports 8 (REAL).');

        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('f')->float(4);
        $alter->execute();
    }

    public function test_modifyColumn(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('SQLite does not support modifying of columns. Creating a new table and copying data instead.');

        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->modifyColumn('id')->int(4);
        $alter->execute();
    }
}
