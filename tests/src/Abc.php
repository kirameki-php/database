<?php declare(strict_types=1);

namespace Tests\Kirameki\Database;

use function dump;

class Abc
{
    public static function test(...$args): void
    {
        dump($args);
        unset($args[0]);
        dump($args);
    }
}
