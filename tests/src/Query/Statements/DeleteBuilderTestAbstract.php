<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Query\Statements;

use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class DeleteBuilderTestAbstract extends QueryTestCase
{
    abstract public function test_deleteAll(): void;

    abstract public function test_delete_with_where(): void;

    abstract public function test_returning(): void;
}
