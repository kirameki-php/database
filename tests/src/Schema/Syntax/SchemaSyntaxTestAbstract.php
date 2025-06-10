<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Schema\Syntax;

use Tests\Kirameki\Database\Schema\SchemaTestCase;

abstract class SchemaSyntaxTestAbstract extends SchemaTestCase
{
    abstract public function test_supportsDdlTransaction(): void;
}
