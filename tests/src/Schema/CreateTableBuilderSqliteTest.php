<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Raw;
use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;
use function implode;
use const PHP_EOL;

class CreateTableBuilderSqliteTest extends CreateTableBuilderTestAbstract
{
    protected string $connection = 'sqlite';

    public function test_id_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->id();
        $builder->execute();
        $this->assertStringStartsWith(
            'CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);' . PHP_EOL .
            'UPDATE "sqlite_sequence" SET "seq" = ',
            $builder->toDdl(),
        );
    }

    public function test_id_column__with_changed_column_name(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->id('userId');
        $builder->execute();
        $this->assertStringStartsWith('CREATE TABLE "users" ("userId" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);', $builder->toDdl());
    }

    public function test_id_column__with_starting_value(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->id(startFrom: 100);
        $builder->execute();
        $this->assertSame(
            'CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);' . PHP_EOL .
            'UPDATE "sqlite_sequence" SET "seq" = 100 WHERE "name" = \'users\';',
            $builder->toDdl(),
        );
    }

    public function test_int_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_int8_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 1)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -256 AND 255) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_int16_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 2)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -65536 AND 65535) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
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
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
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
        $this->assertSame('CREATE TABLE "users" ("id" REAL NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
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
        $this->assertSame('CREATE TABLE "users" ("id" REAL NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
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

    public function test_decimal_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->decimal('price')->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "price" NUMERIC) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_decimal_column__with_precision_size(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->decimal('price', 10, 2)->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "price" NUMERIC) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_timestamp_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->timestamp('id')->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" DATETIME CHECK (datetime("id") IS NOT NULL) PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_timestamp_column__with_precision(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->timestamp('id', 0)->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" DATETIME CHECK (datetime("id") IS NOT NULL) PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_string_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->string('id', 10)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" TEXT CHECK (length("id") <= 10) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_text_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->text('desc')->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "desc" TEXT) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_json_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->json('data')->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "data" JSON_TEXT CHECK (json_valid("data"))) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_binary_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->binary('data')->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "data" BLOB) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_uuid_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" TEXT CHECK (length("id") = 36) PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_notNull(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_autoIncrement(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement();
        $builder->execute();
        $this->assertStringStartsWith(
            'CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);' . PHP_EOL .
            'UPDATE "sqlite_sequence" SET "seq" = ',
            $builder->toDdl(),
        );
    }

    public function test_autoIncrement__with_startFrom(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement(100);
        $builder->execute();
        $this->assertSame(
            'CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);' . PHP_EOL .
            'UPDATE "sqlite_sequence" SET "seq" = 100 WHERE "name" = \'users\';',
            $builder->toDdl(),
        );
    }

    public function test_default__int(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey()->default(1);
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER DEFAULT 1 PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_default__bool(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->bool('id')->nullable()->primaryKey()->default(false);
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" BOOLEAN CHECK ("id" IN (TRUE, FALSE)) DEFAULT FALSE PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_default__float(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id')->nullable()->primaryKey()->default(1.1);
        $this->assertSame('CREATE TABLE "users" ("id" REAL DEFAULT 1.1 PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_default__decimal(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->decimal('id')->nullable()->primaryKey()->default(1.1);
        $this->assertSame('CREATE TABLE "users" ("id" NUMERIC DEFAULT 1.1 PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
    }

    public function test_default__string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->string('id')->nullable()->primaryKey()->default('ABC');
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" TEXT DEFAULT \'ABC\' PRIMARY KEY) WITHOUT ROWID;', $builder->toDdl());
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

    public function test_primaryKey__with_list_string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id', 'category']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER, "category" INTEGER, PRIMARY KEY ("id", "category")) WITHOUT ROWID;', $schema);
    }

    public function test_primaryKey__with_ordering(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id' => SortOrder::Descending, 'category' => SortOrder::Ascending]);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER, "category" INTEGER, PRIMARY KEY ("id", "category")) WITHOUT ROWID;', $schema);
    }

    public function test_primaryKey__without_keys(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Primary key must have at least one column defined.');
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->primaryKey([]);
        $builder->execute();
    }

    public function test_index__with_columns_as_list(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->string('name1', 10)->nullable();
        $builder->string('name2', 10)->nullable();
        $builder->index(['name1', 'name2']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame(implode("\n", [
            'CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "name1" TEXT CHECK (length("name1") <= 10), "name2" TEXT CHECK (length("name2") <= 10)) WITHOUT ROWID;',
            'CREATE INDEX "idx_users_name1_name2" ON "users" ("name1" ASC, "name2" ASC);',
        ]), $schema);
    }

    public function test_index__with_columns_as_map(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->string('name1', 10)->nullable();
        $builder->string('name2', 10)->nullable();
        $builder->index(['name1' => SortOrder::Descending, 'name2' => SortOrder::Ascending]);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame(implode("\n", [
            'CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "name1" TEXT CHECK (length("name1") <= 10), "name2" TEXT CHECK (length("name2") <= 10)) WITHOUT ROWID;',
            'CREATE INDEX "idx_users_name1_name2" ON "users" ("name1" DESC, "name2" ASC);',
        ]), $schema);
    }

    public function test_uniqueIndex__with_columns_as_list(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->string('name1', 10)->nullable();
        $builder->string('name2', 10)->nullable();
        $builder->uniqueIndex(['name1', 'name2']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame(implode("\n", [
            'CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "name1" TEXT CHECK (length("name1") <= 10), "name2" TEXT CHECK (length("name2") <= 10)) WITHOUT ROWID;',
            'CREATE UNIQUE INDEX "idx_users_name1_name2" ON "users" ("name1" ASC, "name2" ASC);',
        ]), $schema);
    }

    public function test_uniqueIndex__with_columns_as_map(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->string('name1', 10)->nullable();
        $builder->string('name2', 10)->nullable();
        $builder->uniqueIndex(['name1' => SortOrder::Descending, 'name2' => SortOrder::Ascending]);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame(implode("\n", [
            'CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "name1" TEXT CHECK (length("name1") <= 10), "name2" TEXT CHECK (length("name2") <= 10)) WITHOUT ROWID;',
            'CREATE UNIQUE INDEX "idx_users_name1_name2" ON "users" ("name1" DESC, "name2" ASC);',
        ]), $schema);
    }

    public function test_foreignKey__with_single_column(): void
    {
        $builder = $this->createTableBuilder('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->foreignKey(['t1Id'], 't1', ['id']);
        $builder->execute();

        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id")) WITHOUT ROWID;', $schema);
    }

    public function test_foreignKey__with_multiple_columns(): void
    {
        $builder = $this->createTableBuilder('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('categoryId')->nullable();
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->int('t1CategoryId')->nullable();
        $builder->foreignKey(['t1Id', 't1CategoryId'], 't1', ['id', 'categoryId']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" INTEGER PRIMARY KEY, "t1Id" INTEGER, "t1CategoryId" INTEGER, FOREIGN KEY ("t1Id", "t1CategoryId") REFERENCES "t1" ("id", "categoryId")) WITHOUT ROWID;', $schema);
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
