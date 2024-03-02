<?php declare(strict_types=1);

namespace Kirameki\Database\Configs;

class DatabaseConfig
{
    /**
     * @param string $adapter
     */
    public function __construct(
        public string $adapter,
    )
    {
    }
}
