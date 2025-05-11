<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class UpsertBuilderTestAbstract extends QueryTestCase
{
    abstract public function test_upsert_value(): void;
}
