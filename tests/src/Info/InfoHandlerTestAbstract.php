<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Info;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\Table\CreateTableBuilder;
use Tests\Kirameki\Database\Query\QueryTestCase;

class InfoHandlerTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function getConnection(): Connection
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
            $t->float('f');
            $t->decimal('d');
            $t->string('t');
            $t->datetime('dt');
            $t->json('j');
            $t->binary('n');
            $t->int('testAId')->references('TestA', 'id');
        });

        $tableInfo = $connection->info()->getTableInfo('TestB');
        $this->assertSame('TestB', $tableInfo->table);

        $column = $tableInfo->columns->get('id');
        $this->assertSame('id', $column->name);
        $this->assertSame('int', $column->type);
        $this->assertSame(false, $column->nullable);
        $this->assertSame(1, $column->position);

        $column = $tableInfo->columns->get('f');
        $this->assertSame('f', $column->name);
        $this->assertSame('float', $column->type);
        $this->assertSame(2, $column->position);

        $column = $tableInfo->columns->get('d');
        $this->assertSame('d', $column->name);
        $this->assertSame('decimal', $column->type);
        $this->assertSame(3, $column->position);

        $column = $tableInfo->columns->get('t');
        $this->assertSame('t', $column->name);
        $this->assertSame('string', $column->type);
        $this->assertSame(4, $column->position);

        $column = $tableInfo->columns->get('dt');
        $this->assertSame('dt', $column->name);
        $this->assertSame('datetime', $column->type);
        $this->assertSame(5, $column->position);

        $column = $tableInfo->columns->get('j');
        $this->assertSame('j', $column->name);
        $this->assertSame('json', $column->type);
        $this->assertSame(6, $column->position);

        $column = $tableInfo->columns->get('n');
        $this->assertSame('n', $column->name);
        $this->assertSame('binary', $column->type);
        $this->assertSame(7, $column->position);

        $index = $tableInfo->indexes->offsetGet(0);
        $this->assertSame('PRIMARY', $index->name);
        $this->assertSame(['id'], $index->columns);
        $this->assertSame('primary', $index->type);

        $foreignKey = $tableInfo->foreignKeys->offsetGet(0);
        $this->assertSame('TestA', $foreignKey->referencedTable);
        $this->assertSame(['id'], $foreignKey->referencedColumns);
        $this->assertSame(['testAId'], $foreignKey->columns);
    }
}
