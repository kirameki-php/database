<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Raw;
use function dump;

class MySql_CreateTableBuilderTest extends SchemaTestCase
{
    protected string $connection = 'mysql';

    public function test_with_no_column(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Table requires at least one column to be defined.');
        $this->createTableBuilder('users')->toDdl();
    }

    public function test_string_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(36) NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_default_int_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int8_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 1)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" TINYINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int16_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 2)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" SMALLINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int32_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 4)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int64_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 8)->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_bool_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->bool('enabled')->nullable()->default(true);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY, "enabled" BOOL DEFAULT TRUE);', $schema);
    }

    public function test_notNull(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_autoIncrement(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement();
        $schema = $builder->toDdl();
        $this->assertStringStartsWith(
            'CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT);' . PHP_EOL .
            'ALTER TABLE "users" AUTO_INCREMENT = '
        , $schema);
    }

    public function test_defaultValue(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey()->default('ABC');
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(36) DEFAULT \'ABC\' PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_using_Raw(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->datetime('loginAt')->nullable()->default(new Raw('CURRENT_TIMESTAMP'));
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "loginAt" DATETIME(6) DEFAULT CURRENT_TIMESTAMP);', $schema);
    }
}
