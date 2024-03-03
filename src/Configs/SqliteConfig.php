<?php declare(strict_types=1);

namespace Kirameki\Database\Configs;

class SqliteConfig extends DatabaseConfig
{
    /**
     * @param string $adapter
     * @param string $filename
     */
    public function __construct(
        string $adapter,
        public string $filename,
    )
    {
        parent::__construct($adapter);
    }
}
