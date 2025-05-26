<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Raw;
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

    public function test_defaultValue(): void
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
}
