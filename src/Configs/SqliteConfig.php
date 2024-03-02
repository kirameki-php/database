<?php declare(strict_types=1);

namespace Kirameki\Database\Configs;

class SqliteConfig extends DatabaseConfig
{
    /**
     * @param string $adapter
     * @param string $path
     */
    public function __construct(
        string $adapter,
        public string $path,
    )
    {
        parent::__construct($adapter);
    }
}
