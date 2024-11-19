<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Info;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Statements\CreateTableBuilder;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function dump;

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
            $t->int('testAId')->references('TestA', 'id');
        });

        $tableInfo = $connection->info()->getTableInfo('TestB');
        $this->assertSame('TestB', $tableInfo->table);

        $column = $tableInfo->columns->get('id');
        $this->assertSame('id', $column->name);
        $this->assertSame('int', $column->type);
        $this->assertSame(false, $column->nullable);
        $this->assertSame(1, $column->position);

        $index = $tableInfo->indexes->offsetGet(0);
        $this->assertSame('PRIMARY', $index->name);
        $this->assertSame(['id'], $index->columns);
        $this->assertSame('primary', $index->type);
    }
}
