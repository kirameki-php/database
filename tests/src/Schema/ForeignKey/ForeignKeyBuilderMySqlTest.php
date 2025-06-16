<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema\ForeignKey;

use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;

class ForeignKeyBuilderMySqlTest extends ForeignKeyBuilderTestAbstract
{
    protected string $connection = 'mysql';

    public function test_foreignKey__no_name(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id']);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id"));', $schema);
        $this->assertSame('t2_ibfk_1', $connection->info()->getTableInfo('t2')->foreignKeys->single()->name);
    }

    public function test_foreignKey__with_name(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->name('fk_t1');
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, CONSTRAINT "fk_t1" FOREIGN KEY ("t1Id") REFERENCES "t1" ("id"));', $schema);
        $this->assertSame('fk_t1', $connection->info()->getTableInfo('t2')->foreignKeys->single()->name);
    }

    public function test_foreignKey__with_on_delete_no_action(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onDelete(ReferenceOption::NoAction);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE NO ACTION);', $schema);
    }

    public function test_foreignKey__with_on_delete_set_null(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onDelete(ReferenceOption::SetNull);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE SET NULL);', $schema);
    }

    public function test_foreignKey__with_on_delete_set_default(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onDelete(ReferenceOption::SetDefault);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE SET DEFAULT);', $schema);
    }

    public function test_foreignKey__with_on_delete_cascade(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onDelete(ReferenceOption::Cascade);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE CASCADE);', $schema);
    }

    public function test_foreignKey__with_on_delete_restrict(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onDelete(ReferenceOption::Restrict);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE RESTRICT);', $schema);
    }

    public function test_foreignKey__with_on_update_no_action(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onUpdate(ReferenceOption::NoAction);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE NO ACTION);', $schema);
    }

    public function test_foreignKey__with_on_update_set_null(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onUpdate(ReferenceOption::SetNull);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE SET NULL);', $schema);
    }

    public function test_foreignKey__with_on_update_set_default(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onUpdate(ReferenceOption::SetDefault);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE SET DEFAULT);', $schema);
    }

    public function test_foreignKey__with_on_update_cascade(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onUpdate(ReferenceOption::Cascade);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE CASCADE);', $schema);
    }

    public function test_foreignKey__with_on_update_restrict(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])->onUpdate(ReferenceOption::Restrict);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE RESTRICT);', $schema);
    }

    public function test_foreignKey__with_both_on_delete_and_on_update(): void
    {
        $connection = $this->connect();
        $builder = $connection->schema()->createTable('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id'])
            ->onUpdate(ReferenceOption::SetNull)
            ->onDelete(ReferenceOption::Cascade);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame(
            'CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE CASCADE ON UPDATE SET NULL);',
            $schema
        );
    }
}
