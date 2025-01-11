<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Functions;

use Kirameki\Database\Connection;
use Tests\Kirameki\Database\Query\QueryTestCase;

abstract class UuidTestAbstract extends QueryTestCase
{
    protected string $useConnection;

    protected function getConnection(): Connection
    {
        return $this->createTempConnection($this->useConnection);
    }

    abstract public function test_instantiate(): void;
}
