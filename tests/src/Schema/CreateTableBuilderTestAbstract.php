<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Database\Raw;

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

    abstract public function test_defaultValue(): void;

    abstract public function test_defaultValue_using_Raw(): void;
}
