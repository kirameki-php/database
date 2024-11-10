<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

class SqliteAdapterTest extends PdoAdapterTestAbstract
{
    protected string $useConnection = 'sqlite';

}
