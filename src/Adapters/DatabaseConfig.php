<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Support\TagsFormat;
use Override;

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
