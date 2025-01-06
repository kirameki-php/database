<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Database\Connection;
use Tests\Kirameki\Database\Query\QueryTestCase;
use function dump;

abstract class CoalesceTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function getConnection(): Connection
    {
        return $this->createTempConnection($this->useConnection);
    }

    abstract public function test_values_construct(): void;

    abstract public function test_columns_construct(): void;
}
