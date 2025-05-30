<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Raw;
use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;

class CreateTableBuilderMySqlTest extends CreateTableBuilderTestAbstract
{
    protected string $connection = 'mysql';

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
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY, "enabled" BIT(1) DEFAULT TRUE);', $schema);
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

    public function test_defaultValue_int(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey()->default(1);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT DEFAULT 1 PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_bool(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->bool('id')->nullable()->primaryKey()->default(false);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIT(1) DEFAULT FALSE PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_float(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id')->nullable()->primaryKey()->default(1.1);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" FLOAT DEFAULT 1.1 PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_string(): void
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
        $builder->timestamp('loginAt')->nullable()->default(new Raw('CURRENT_TIMESTAMP'));
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "loginAt" DATETIME(6) DEFAULT CURRENT_TIMESTAMP);', $schema);
    }

    public function test_references(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id');
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "roleId" BIGINT REFERENCES "roles" ("id"));', $schema);
    }

    public function test_references_with_delete_options(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id', ReferenceOption::NoAction);
        $schema = $builder->toDdl();
        $this->assertSame(
            'CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "roleId" BIGINT REFERENCES "roles" ("id") ON DELETE NO ACTION);',
            $schema
        );
    }

    public function test_references_with_delete_and_update_options(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id', ReferenceOption::Cascade, ReferenceOption::SetNull);
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "roleId" BIGINT REFERENCES "roles" ("id") ON DELETE CASCADE ON UPDATE SET NULL);', $schema);
    }
}
