<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Raw;
use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;
use const PHP_EOL;

class CreateTableBuilderSqliteTest extends CreateTableBuilderTestAbstract
{
    protected string $connection = 'sqlite';

    public function test_int_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int8_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 1)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -256 AND 255) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int16_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 2)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -65536 AND 65535) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int32_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 4)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -4294967296 AND 4294967295) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int64_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 8)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int_column__with_invalid_size(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"id" has an invalid integer size: 3. Only 1, 2, 4, 8 are supported.');
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 3)->primaryKey();
        $builder->execute();
    }

    public function test_float_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id')->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" REAL NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_float32_column(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"id" has invalid float size: 4. Sqlite only supports 8 (REAL)');
        $builder = $this->createTableBuilder('users');
        $builder->float('id', 4)->primaryKey();
        $builder->execute();
    }

    public function test_float64_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id', 8)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" REAL NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_float_column__with_invalid_size(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"id" has invalid float size: 2. Sqlite only supports 8 (REAL).');
        $builder = $this->createTableBuilder('users');
        $builder->float('id', 2)->primaryKey();
        $builder->execute();
    }

    public function test_bool_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->bool('enabled')->nullable()->default(true);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY, "enabled" BOOLEAN CHECK ("enabled" IN (TRUE, FALSE)) DEFAULT TRUE) WITHOUT ROWID;', $schema);
    }

    public function test_string_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" TEXT CHECK (length("id") = 36) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_notNull(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_autoIncrement(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertStringStartsWith(
            'CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);' . PHP_EOL .
            'UPDATE "sqlite_sequence" SET "seq" = '
            , $schema);
    }

    public function test_autoIncrement_with_startingValue(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement(100);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame(
            'CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);' . PHP_EOL .
            'UPDATE "sqlite_sequence" SET "seq" = 100 WHERE "name" = \'users\';',
            $schema
        );
    }

    public function test_defaultValue_int(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey()->default(1);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER DEFAULT 1 PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_defaultValue_bool(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->bool('id')->nullable()->primaryKey()->default(false);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BOOLEAN CHECK ("id" IN (TRUE, FALSE)) DEFAULT FALSE PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_defaultValue_float(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id')->nullable()->primaryKey()->default(1.1);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" REAL DEFAULT 1.1 PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_defaultValue_string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey()->default('ABC');
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" TEXT CHECK (length("id") = 36) DEFAULT \'ABC\' PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_defaultValue_using_Raw(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->timestamp('loginAt')->nullable()->default(new Raw('CURRENT_TIMESTAMP'));
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "loginAt" DATETIME CHECK (datetime("loginAt") IS NOT NULL) DEFAULT CURRENT_TIMESTAMP) WITHOUT ROWID;', $schema);
    }

    public function test_primaryKey_list_string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id', 'category']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER, "category" INTEGER, PRIMARY KEY ("id", "category")) WITHOUT ROWID;', $schema);
    }

    public function test_primaryKey_with_ordering(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id' => SortOrder::Descending, 'category' => SortOrder::Ascending]);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER, "category" INTEGER, PRIMARY KEY ("id", "category")) WITHOUT ROWID;', $schema);
    }

    public function test_references(): void
    {
        $builderRoles = $this->createTableBuilder('roles');
        $builderRoles->int('id')->primaryKey();
        $builderRoles->execute();

        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id');
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "roleId" INTEGER REFERENCES "roles" ("id")) WITHOUT ROWID;', $schema);
    }

    public function test_references_with_delete_options(): void
    {
        $builderRoles = $this->createTableBuilder('roles');
        $builderRoles->int('id')->primaryKey();
        $builderRoles->execute();

        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id', onDelete: ReferenceOption::NoAction);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "roleId" INTEGER REFERENCES "roles" ("id") ON DELETE NO ACTION) WITHOUT ROWID;', $schema);
    }

    public function test_references_with_delete_and_update_options(): void
    {
        $builderRoles = $this->createTableBuilder('roles');
        $builderRoles->int('id')->primaryKey();
        $builderRoles->execute();

        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id', ReferenceOption::Cascade, ReferenceOption::SetNull);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "roleId" INTEGER REFERENCES "roles" ("id") ON DELETE CASCADE ON UPDATE SET NULL) WITHOUT ROWID;', $schema);
    }
}
