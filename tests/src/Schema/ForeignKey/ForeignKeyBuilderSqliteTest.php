<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema\ForeignKey;

use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;

class ForeignKeyBuilderSqliteTest extends ForeignKeyBuilderTestAbstract
{
    protected string $connection = 'sqlite';

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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id")) WITHOUT ROWID;', $schema);
        $this->assertSame('0', $connection->info()->getTableInfo('t2')->foreignKeys->single()->name);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, CONSTRAINT "fk_t1" FOREIGN KEY ("t1Id") REFERENCES "t1" ("id")) WITHOUT ROWID;', $schema);
        $this->assertSame('0', $connection->info()->getTableInfo('t2')->foreignKeys->single()->name);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE NO ACTION) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE SET NULL) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE SET DEFAULT) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE CASCADE) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE RESTRICT) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE NO ACTION) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE SET NULL) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE SET DEFAULT) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE CASCADE) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON UPDATE RESTRICT) WITHOUT ROWID;', $schema);
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
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id") ON DELETE CASCADE ON UPDATE SET NULL) WITHOUT ROWID;', $schema);
    }
}
