<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

class MigrationConfig
{
    /**
     * @param string|null $connection
     * @param string $table
     */
    public function __construct(
        public ?string $connection = null,
        public string $table = 'Migration',
    )
    {
    }
}
