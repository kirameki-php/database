<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class InsertBuilderTestAbstract extends QueryTestCase
{
    abstract public function test_insert_value(): void;

    abstract public function test_insert_values(): void;

    abstract public function test_insert_partial_values(): void;

    abstract public function test_insert_integer(): void;

    abstract public function test_insert_string(): void;

    abstract public function test_insert_DateTime(): void;

    abstract public function test_returning(): void;
}
