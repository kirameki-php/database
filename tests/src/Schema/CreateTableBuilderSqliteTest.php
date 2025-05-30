<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Raw;
use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;
use stdClass;
use function dump;
use const PHP_EOL;

class CreateTableBuilderSqliteTest extends CreateTableBuilderTestAbstract
{
    protected string $connection = 'sqlite';

    public function test_string_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" TEXT CHECK (length("id") = 36) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_default_int_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int8_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 1)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -256 AND 255) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int16_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 2)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -65536 AND 65535) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int32_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 4)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER CHECK ("id" BETWEEN -4294967296 AND 4294967295) NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_int64_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 8)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_bool_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->bool('enabled')->nullable()->default(true);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY, "enabled" BOOLEAN CHECK ("enabled" IN (TRUE, FALSE)) DEFAULT TRUE) WITHOUT ROWID;', $schema);
    }

    public function test_notNull(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_autoIncrement(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement();
        $schema = $builder->toDdl();
        $this->assertStringStartsWith(
            'CREATE TABLE "users" ("id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT);' . PHP_EOL .
            'UPDATE "sqlite_sequence" SET "seq" = '
            , $schema);
    }

    public function test_defaultValue_int(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey()->default(1);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER DEFAULT 1 PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_defaultValue_bool(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->bool('id')->nullable()->primaryKey()->default(false);
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
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" TEXT CHECK (length("id") = 36) DEFAULT \'ABC\' PRIMARY KEY) WITHOUT ROWID;', $schema);
    }

    public function test_defaultValue_using_Raw(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->timestamp('loginAt')->nullable()->default(new Raw('CURRENT_TIMESTAMP'));
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "loginAt" DATETIME CHECK (datetime("loginAt") IS NOT NULL) DEFAULT CURRENT_TIMESTAMP) WITHOUT ROWID;', $schema);
    }

    public function test_references(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id');
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "roleId" INTEGER REFERENCES "roles" ("id")) WITHOUT ROWID;', $schema);
    }

    public function test_references_with_delete_options(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id', onDelete: ReferenceOption::NoAction);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "roleId" INTEGER REFERENCES "roles" ("id") ON DELETE NO ACTION) WITHOUT ROWID;', $schema);
    }

    public function test_references_with_delete_and_update_options(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id', ReferenceOption::Cascade, ReferenceOption::SetNull);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INTEGER PRIMARY KEY, "roleId" INTEGER REFERENCES "roles" ("id") ON DELETE CASCADE ON UPDATE SET NULL) WITHOUT ROWID;', $schema);
    }
}
