<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Expressions;

use Kirameki\Database\DatabaseConnection;
use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class AggregateTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function connect(): DatabaseConnection
    {
        return $this->createTempConnection($this->useConnection);
    }

    abstract public function test_aggregate_min(): void;

    abstract public function test_aggregate_min__with_custom_as_name(): void;

    abstract public function test_aggregate_max(): void;

    abstract public function test_aggregate_max__with_custom_as_name(): void;

    abstract public function test_aggregate_count(): void;

    abstract public function test_aggregate_count__with_custom_as_name(): void;

    abstract public function test_aggregate_avg(): void;

    abstract public function test_aggregate_avg__with_custom_as_name(): void;

    abstract public function test_aggregate_sum(): void;

    abstract public function test_aggregate_sum__with_custom_as_name(): void;

    abstract public function test_aggregate_sum__with_total_larger_than_int64(): void;
}
