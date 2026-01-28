<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Info;

use Kirameki\Database\DatabaseConnection;
use Kirameki\Database\Info\Statements\ColumnType;
use Kirameki\Database\Schema\Statements\Table\CreateTableBuilder;
use Tests\Kirameki\Database\Query\QueryTestCase;

class InfoHandlerTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function getConnection(): DatabaseConnection
    {
        return $this->createTempConnection($this->useConnection);
    }

    public function test_getTableNames(): void
    {
        $connection = $this->getConnection();

        $schema = $connection->schema();
        $schema->createTable('TestA')->run(static fn(CreateTableBuilder $t) => $t->int('id')->primaryKey());
        $schema->createTable('TestB')->run(static fn(CreateTableBuilder $t) => $t->int('id')->primaryKey());

        $tables = $connection->info()->getTableNames();
        $this->assertSame(['TestA', 'TestB'], $tables->all());
    }

    public function test_getTableColumns(): void
    {
        $connection = $this->getConnection();

        $schema = $connection->schema();
        $schema->createTable('TestA')->run(static function (CreateTableBuilder $t) {
            $t->int('id')->primaryKey();
        });
        $schema->createTable('TestB')->run(static function (CreateTableBuilder $t) {
            $t->int('id')->primaryKey();
            $t->decimal('d');
            $t->string('t');
            $t->int('testAId')->references('TestA', 'id');
        });

        $tableInfo = $connection->info()->getTableInfo('TestB');
        $this->assertSame('TestB', $tableInfo->table);

        $column = $tableInfo->columns->get('d');
        $this->assertSame('d', $column->name);
        $this->assertSame(2, $column->position);

        $column = $tableInfo->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(3, $column->position);


        $index = $tableInfo->indexes->offsetGet(0);
        $this->assertSame('PRIMARY', $index->name);
        $this->assertSame(['id'], $index->columns);
        $this->assertSame('primary', $index->type);

        $foreignKey = $tableInfo->foreignKeys->offsetGet(0);
        $this->assertSame('TestA', $foreignKey->referencedTable);
        $this->assertSame(['id'], $foreignKey->referencedColumns);
        $this->assertSame(['testAId'], $foreignKey->columns);
    }

    public function test_getTableColumns_type_int(): void
    {
        $connection = $this->getConnection();
        $connection->schema()->createTable('Test')->run(static function (CreateTableBuilder $t) {
            $t->int('t')->primaryKey();
        });

        $column = $connection->info()->getTableInfo('Test')->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(ColumnType::Int, $column->type);
    }

    public function test_getTableColumns_type_float(): void
    {
        $connection = $this->getConnection();
        $connection->schema()->createTable('Test')->run(static function (CreateTableBuilder $t) {
            $t->int('i')->primaryKey();
            $t->float('t');
        });

        $column = $connection->info()->getTableInfo('Test')->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(ColumnType::Float, $column->type);
    }

    public function test_getTableColumns_type_decimal(): void
    {
        $connection = $this->getConnection();
        $connection->schema()->createTable('Test')->run(static function (CreateTableBuilder $t) {
            $t->int('i')->primaryKey();
            $t->decimal('t');
        });

        $column = $connection->info()->getTableInfo('Test')->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(ColumnType::Decimal, $column->type);
    }

    public function test_getTableColumns_type_bool(): void
    {
        $connection = $this->getConnection();
        $connection->schema()->createTable('Test')->run(static function (CreateTableBuilder $t) {
            $t->int('i')->primaryKey();
            $t->bool('t');
        });

        $column = $connection->info()->getTableInfo('Test')->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(ColumnType::Bool, $column->type);
    }

    public function test_getTableColumns_type_string(): void
    {
        $connection = $this->getConnection();
        $connection->schema()->createTable('Test')->run(static function (CreateTableBuilder $t) {
            $t->int('i')->primaryKey();
            $t->string('t');
        });

        $column = $connection->info()->getTableInfo('Test')->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(ColumnType::String, $column->type);
    }

    public function test_getTableColumns_type_datetime(): void
    {
        $connection = $this->getConnection();
        $connection->schema()->createTable('Test')->run(static function (CreateTableBuilder $t) {
            $t->int('i')->primaryKey();
            $t->timestamp('t');
        });

        $column = $connection->info()->getTableInfo('Test')->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(ColumnType::Timestamp, $column->type);
    }

    public function test_getTableColumns_type_json(): void
    {
        $connection = $this->getConnection();
        $connection->schema()->createTable('Test')->run(static function (CreateTableBuilder $t) {
            $t->int('i')->primaryKey();
            $t->json('t');
        });

        $column = $connection->info()->getTableInfo('Test')->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame(ColumnType::Json, $column->type);
    }
}
