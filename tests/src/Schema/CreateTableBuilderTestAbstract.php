<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Schema\Statements\Table\CreateTableStatement;
use Random\Engine\PcgOneseq128XslRr64;
use Random\Randomizer;
use stdClass;

abstract class CreateTableBuilderTestAbstract extends SchemaTestCase
{
    public function test___construct_with_randomizer(): void
    {
        $randomizer = new Randomizer(new PcgOneseq128XslRr64(1));
        $connection = $this->createTempConnection($this->connection, randomizer: $randomizer);
        $schema = $connection->schema();
        $builder = $schema->createTable('users');
        $builder->id();
        $builder->execute();
        $schema = $builder->toDdl();
        $this->assertStringContainsString('= 9267194', $schema);
    }

    public function test_execute(): void
    {
        $builder = $this->createTableBuilder('users');
        $builder->int('id')->primaryKey();
        $result = $builder->execute();

        $this->assertInstanceOf(CreateTableStatement::class, $result->statement);
        $this->assertCount(1, $result->commands);
        $this->assertGreaterThan(0, $result->elapsedMs);
    }

    public function test_with_no_column(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Table requires at least one column to be defined.');
        $this->createTableBuilder('users')->toDdl();
    }

    abstract public function test_id_column(): void;

    abstract public function test_id_column__with_changed_column_name(): void;

    abstract public function test_id_column__with_starting_value(): void;

    abstract public function test_int_column(): void;

    abstract public function test_int8_column(): void;

    abstract public function test_int16_column(): void;

    abstract public function test_int32_column(): void;

    abstract public function test_int64_column(): void;

    abstract public function test_int_column__with_invalid_size(): void;

    abstract public function test_float_column(): void;

    abstract public function test_float32_column(): void;

    abstract public function test_float64_column(): void;

    abstract public function test_float_column__with_invalid_size(): void;

    abstract public function test_bool_column(): void;

    abstract public function test_decimal_column(): void;

    abstract public function test_decimal_column__with_precision_size(): void;

    abstract public function test_timestamp_column(): void;

    abstract public function test_timestamp_column__with_precision(): void;

    abstract public function test_string_column(): void;

    abstract public function test_notNull(): void;

    abstract public function test_autoIncrement(): void;

    abstract public function test_autoIncrement__with_startFrom(): void;

    abstract public function test_default__int(): void;

    abstract public function test_default__bool(): void;

    abstract public function test_default__float(): void;

    abstract public function test_default__decimal(): void;

    abstract public function test_default__string(): void;

    abstract public function test_defaultValue_using_Raw(): void;

    public function test_defaultValue_invalid_value(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown default value type: stdClass');
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey()->default(new stdClass());
        $builder->execute();
    }

    abstract public function test_primaryKey__with_list_string(): void;

    abstract public function test_primaryKey__with_ordering(): void;

    abstract public function test_primaryKey__without_keys(): void;

    abstract public function test_references(): void;

    abstract public function test_references_with_delete_options(): void;

    abstract public function test_references_with_delete_and_update_options(): void;
}
