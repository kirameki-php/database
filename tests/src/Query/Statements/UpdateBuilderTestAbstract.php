<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class UpdateBuilderTestAbstract extends QueryTestCase
{
    abstract public function test_update_value(): void;

    abstract public function test_update_values(): void;

    abstract public function test_update_with_where(): void;

    abstract public function test_returning(): void;
}
