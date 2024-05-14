<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

class DatabaseConfig
{
    /**
     * @param string $default
     * @param array<string, ConnectionConfig> $connections
     */
    public function __construct(
        public array $connections,
        public ?string $default = null,
    )
    {
    }
}
