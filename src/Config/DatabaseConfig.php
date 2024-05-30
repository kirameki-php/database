<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

class DatabaseConfig
{
    /**
     * @param array<string, ConnectionConfig> $connections
     * @param string|null $default
     * @param MigrationConfig|null $migration
     * @param bool $dropProtection
     */
    public function __construct(
        public array $connections,
        public ?string $default = null,
        public ?MigrationConfig $migration = null,
        public bool $dropProtection = false,
    )
    {
    }
}
