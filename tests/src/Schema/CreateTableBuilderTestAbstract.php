<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Raw;
use stdClass;

abstract class CreateTableBuilderTestAbstract extends SchemaTestCase
{
    public function test_with_no_column(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Table requires at least one column to be defined.');
        $this->createTableBuilder('users')->toDdl();
    }

    abstract public function test_string_column(): void;

    abstract public function test_default_int_column(): void;

    abstract public function test_int8_column(): void;

    abstract public function test_int16_column(): void;

    abstract public function test_int32_column(): void;

    abstract public function test_int64_column(): void;

    abstract public function test_bool_column(): void;

    abstract public function test_notNull(): void;

    abstract public function test_autoIncrement(): void;

    abstract public function test_defaultValue_int(): void;

    abstract public function test_defaultValue_bool(): void;

    abstract public function test_defaultValue_float(): void;

    abstract public function test_defaultValue_string(): void;

    abstract public function test_defaultValue_using_Raw(): void;

    public function test_defaultValue_invalid_value(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown default value type: stdClass');
        $builder = $this->createTableBuilder('users');
        $builder->uuid('id')->nullable()->primaryKey()->default(new stdClass());
        $builder->toDdl();
    }

    abstract public function test_references(): void;

    abstract public function test_references_with_delete_options(): void;

    abstract public function test_references_with_delete_and_update_options(): void;
}
