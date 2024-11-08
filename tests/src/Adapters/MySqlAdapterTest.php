<?php declare(strict_types=1);

namespace Tests\Kirameki\Database\Adapters;

class MySqlAdapterTest extends PdoAdapterTestAbstract
{
    protected string $useConnection = 'mysql';
}
