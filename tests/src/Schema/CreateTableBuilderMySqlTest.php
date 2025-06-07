<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Raw;
use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;

class CreateTableBuilderMySqlTest extends CreateTableBuilderTestAbstract
{
    protected string $connection = 'mysql';

    public function test_string_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(36) NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_default_int_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int8_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 1)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" TINYINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int16_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 2)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" SMALLINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int32_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 4)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" INT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_int64_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 8)->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_bool_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->bool('enabled')->nullable()->default(true);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY, "enabled" BIT(1) DEFAULT TRUE);', $schema);
    }

    public function test_notNull(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $schema);
    }

    public function test_autoIncrement(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement();
        $builder->execute();
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
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT DEFAULT 1 PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_bool(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->bool('id')->nullable()->primaryKey()->default(false);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIT(1) DEFAULT FALSE PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_float(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id')->nullable()->primaryKey()->default(1.1);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" FLOAT DEFAULT 1.1 PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey()->default('ABC');
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(36) DEFAULT \'ABC\' PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_using_Raw(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->timestamp('loginAt')->nullable()->default(new Raw('CURRENT_TIMESTAMP(6)'));
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "loginAt" DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6));', $schema);
    }

    public function test_primaryKey_list_string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id', 'category']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT, "category" BIGINT, PRIMARY KEY ("id" ASC, "category" ASC));', $schema);
    }

    public function test_primaryKey_with_ordering(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id' => SortOrder::Descending, 'category' => SortOrder::Ascending]);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT, "category" BIGINT, PRIMARY KEY ("id" DESC, "category" ASC));', $schema);
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
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "roleId" BIGINT REFERENCES "roles" ("id"));', $schema);
    }

    public function test_references_with_delete_options(): void
    {
        $builderRoles = $this->createTableBuilder('roles');
        $builderRoles->int('id')->primaryKey();
        $builderRoles->execute();

        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('roleId')->nullable()->references('roles', 'id', ReferenceOption::NoAction);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "roleId" BIGINT REFERENCES "roles" ("id") ON DELETE NO ACTION);', $schema);
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
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "roleId" BIGINT REFERENCES "roles" ("id") ON DELETE CASCADE ON UPDATE SET NULL);', $schema);
    }
}
