<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Database\Info\Statements\ColumnType;
use function random_int;

class AlterTableBuilderMySqlTest extends AlterTableBuilderTestAbstract
{
    protected string $connection = 'mysql';

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
            "ALTER TABLE \"{$table}\" ADD COLUMN \"i\" BIGINT NOT NULL;",
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
            "ALTER TABLE \"{$table}\" ADD COLUMN \"i\" SMALLINT NOT NULL;",
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
            "ALTER TABLE \"{$table}\" ADD COLUMN \"f\" DOUBLE NOT NULL;",
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
        $alter->addColumn('f')->float(4);
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('f');
        $this->assertSame(ColumnType::Float, $info->type);
        $this->assertSame('f', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(2, $info->position);
        $this->assertSame(
            "ALTER TABLE \"{$table}\" ADD COLUMN \"f\" FLOAT NOT NULL;",
            $alter->toDdl(),
        );
    }

    public function test_addColumn__float_with_invalid_size(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('"f" has an invalid float size: 2. MySQL only supports 4 (FLOAT) and 8 (DOUBLE).');

        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->addColumn('f')->float(2);
        $alter->execute();
    }

    public function test_modifyColumn(): void
    {
        $table = 'tmp_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table);
        $create->id();
        $create->execute();
        $alter = $schema->alterTable($table);
        $alter->modifyColumn('id')->int(4);
        $alter->execute();

        $info = $connection->info()->getTableInfo($table)->columns->get('id');
        $this->assertSame(ColumnType::Int, $info->type);
        $this->assertSame('id', $info->name);
        $this->assertFalse($info->nullable);
        $this->assertSame(1, $info->position);
        $this->assertSame(
            "ALTER TABLE \"{$table}\" MODIFY COLUMN \"id\" INT NOT NULL;",
            $alter->toDdl(),
        );
    }

    public function test_addForeignKey(): void
    {
        $table1 = 'tmp1_' . random_int(1000, 9999);
        $table2 = 'tmp2_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table1);
        $create->id();
        $create->execute();

        $create = $schema->createTable($table2);
        $create->id();
        $create->int('otherId')->nullable();
        $create->uniqueIndex(['otherId']);
        $create->execute();

        $alter = $schema->alterTable($table1);
        $alter->addForeignKey(['id'], $table2, ['otherId']);
        $alter->execute();

        $keys = $connection->info()->getTableInfo($table1)->foreignKeys;
        $this->assertSame("{$table1}_ibfk_1", $keys[0]->name);
    }

    public function test_dropForeignKey(): void
    {
        $table1 = 'tmp1_' . random_int(1000, 9999);
        $table2 = 'tmp2_' . random_int(1000, 9999);
        $connection = $this->connect();
        $schema = $connection->schema();
        $create = $schema->createTable($table1);
        $create->id();
        $create->execute();

        $create = $schema->createTable($table2);
        $create->id();
        $create->int('otherId')->nullable();
        $create->uniqueIndex(['otherId']);
        $create->execute();

        $alter = $schema->alterTable($table1);
        $alter->addForeignKey(['id'], $table2, ['otherId']);
        $alter->execute();

        $fk = $connection->info()->getTableInfo($table1)->foreignKeys[0]->name;
        $drop = $schema->alterTable($table1);
        $drop->dropForeignKey($fk);
        $drop->execute();

        $this->assertCount(0, $connection->info()->getTableInfo($table1)->foreignKeys);
    }
}
