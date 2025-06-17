<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Info\Statements\ColumnType;
use Kirameki\Database\Schema\Statements\Table\AlterTableStatement;
use function dump;

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
    }
}
