<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Exceptions\LogicException;
use Kirameki\Database\Query\Statements\SortOrder;
use Kirameki\Database\Raw;
use Kirameki\Database\Schema\Statements\ForeignKey\ReferenceOption;
use function implode;
use const PHP_EOL;

class CreateTableBuilderMySqlTest extends CreateTableBuilderTestAbstract
{
    protected string $connection = 'mysql';

    public function test_id_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->id();
        $builder->execute();
        $this->assertStringStartsWith(
            'CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT);' . PHP_EOL .
            'ALTER TABLE "users" AUTO_INCREMENT = ',
            $builder->toDdl(),
        );
    }

    public function test_id_column__with_changed_column_name(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->id('userId');
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertStringStartsWith('CREATE TABLE "users" ("userId" BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT);', $schema);
    }

    public function test_id_column__with_starting_value(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->id(startFrom: 100);
        $builder->execute();
        $this->assertSame(
            'CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT);' . PHP_EOL .
            'ALTER TABLE "users" AUTO_INCREMENT = 100;',
            $builder->toDdl(),
        );
    }

    public function test_int_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $builder->toDdl());
    }

    public function test_int8_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 1)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" TINYINT NOT NULL PRIMARY KEY);', $builder->toDdl());
    }

    public function test_int16_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 2)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" SMALLINT NOT NULL PRIMARY KEY);', $builder->toDdl());
    }

    public function test_int32_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 4)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" INT NOT NULL PRIMARY KEY);', $builder->toDdl());
    }

    public function test_int64_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 8)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY);', $builder->toDdl());
    }

    public function test_int_column__with_invalid_size(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"id" has an invalid integer size: 3. MySQL only supports 1 (TINYINT), 2 (SMALLINT), 4 (INT), and 8 (BIGINT).');
        $builder = $this->createTableBuilder('users');
        $builder->int('id', 3)->primaryKey();
        $builder->execute();
    }

    public function test_float_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id')->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" DOUBLE PRIMARY KEY);', $builder->toDdl());
    }

    public function test_float32_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id', 4)->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" FLOAT PRIMARY KEY);', $builder->toDdl());
    }

    public function test_float64_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id', 8)->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" DOUBLE PRIMARY KEY);', $builder->toDdl());
    }

    public function test_float_column__with_invalid_size(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"score" has an invalid float size: 3. MySQL only supports 4 (FLOAT) and 8 (DOUBLE).');
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $builder->float('score', 3);
        $builder->execute();
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

    public function test_decimal_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->decimal('price')->nullable();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "price" DECIMAL(65, 30));', $builder->toDdl());
    }

    public function test_decimal_column__with_precision_size(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->decimal('price', 10, 2)->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "price" DECIMAL(10, 2));', $builder->toDdl());
    }

    public function test_timestamp_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->timestamp('id')->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" DATETIME(6) PRIMARY KEY);', $builder->toDdl());
    }

    public function test_timestamp_column__with_precision(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->timestamp('id', 0)->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" DATETIME(0) PRIMARY KEY);', $builder->toDdl());
    }

    public function test_string_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->string('id', 10)->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(10) NOT NULL PRIMARY KEY);', $builder->toDdl());
    }

    public function test_text_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->text('desc')->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "desc" LONGTEXT);', $builder->toDdl());
    }

    public function test_json_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->json('data')->nullable();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "data" JSON);', $builder->toDdl());
    }

    public function test_uuid_column(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey();
        $builder->execute();
        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(36) PRIMARY KEY);', $builder->toDdl());
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

    public function test_autoIncrement__with_startFrom(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey()->autoIncrement(100);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame(
            'CREATE TABLE "users" ("id" BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT);' . PHP_EOL .
            'ALTER TABLE "users" AUTO_INCREMENT = 100;'
        , $schema);
    }

    public function test_default__int(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey()->default(1);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT DEFAULT 1 PRIMARY KEY);', $schema);
    }

    public function test_default__bool(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->bool('id')->nullable()->primaryKey()->default(false);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIT(1) DEFAULT FALSE PRIMARY KEY);', $schema);
    }

    public function test_default__float(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->float('id')->nullable()->primaryKey()->default(1.1);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" DOUBLE DEFAULT 1.1 PRIMARY KEY);', $schema);
    }

    public function test_default__decimal(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->decimal('id')->nullable()->primaryKey()->default(1.1);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" DECIMAL(65, 30) DEFAULT 1.1 PRIMARY KEY);', $schema);
    }

    public function test_default__timestamp(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->timestamp('id')->nullable()->primaryKey()->default('2023-01-01 00:00:00');
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" DATETIME(6) DEFAULT \'2023-01-01 00:00:00\' PRIMARY KEY);', $schema);
    }

    public function test_default__string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->string('id')->nullable()->primaryKey()->default('ABC');
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(191) DEFAULT \'ABC\' PRIMARY KEY);', $schema);
    }

    public function test_default__uuid(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey()->generateDefault();
        $builder->execute();
        $schema = $builder->toDdl();

        $this->assertSame('CREATE TABLE "users" ("id" VARCHAR(36) DEFAULT (UUID()) PRIMARY KEY);', $schema);
    }

    public function test_defaultValue_using_Raw(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable()->primaryKey();
        $builder->timestamp('loginAt')->nullable()->default(new Raw('CURRENT_TIMESTAMP(6)'));
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "loginAt" DATETIME(6) DEFAULT (CURRENT_TIMESTAMP(6)));', $schema);
    }

    public function test_primaryKey__with_list_string(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id', 'category']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT, "category" BIGINT, PRIMARY KEY ("id" ASC, "category" ASC));', $schema);
    }

    public function test_primaryKey__with_ordering(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->nullable();
        $builder->int('category')->nullable();
        $builder->primaryKey(['id' => SortOrder::Descending, 'category' => SortOrder::Ascending]);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "users" ("id" BIGINT, "category" BIGINT, PRIMARY KEY ("id" DESC, "category" ASC));', $schema);
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
            'CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "name1" VARCHAR(10), "name2" VARCHAR(10));',
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
            'CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "name1" VARCHAR(10), "name2" VARCHAR(10));',
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
            'CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "name1" VARCHAR(10), "name2" VARCHAR(10));',
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
            'CREATE TABLE "users" ("id" BIGINT PRIMARY KEY, "name1" VARCHAR(10), "name2" VARCHAR(10));',
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
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, FOREIGN KEY ("t1Id") REFERENCES "t1" ("id"));', $schema);
    }

    public function test_foreignKey__with_multiple_columns(): void
    {
        $builder = $this->createTableBuilder('t1');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('categoryId')->nullable();
        $builder->uniqueIndex(['id', 'categoryId']);
        $builder->execute();

        $builder = $this->createTableBuilder('t2');
        $builder->int('id')->nullable()->primaryKey();
        $builder->int('t1Id')->nullable();
        $builder->int('t1CategoryId')->nullable();
        $builder->foreignKey(['t1Id', 't1CategoryId'], 't1', ['id', 'categoryId']);
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertSame('CREATE TABLE "t2" ("id" BIGINT PRIMARY KEY, "t1Id" BIGINT, "t1CategoryId" BIGINT, FOREIGN KEY ("t1Id", "t1CategoryId") REFERENCES "t1" ("id", "categoryId"));', $schema);
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
