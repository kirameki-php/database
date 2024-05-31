<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

use Kirameki\Database\Query\Support\TagsFormat;

class DatabaseConfig
{
    /**
     * @param array<string, ConnectionConfig> $connections
     * @param string|null $default
     * @param MigrationConfig|null $migration
     * @param bool $dropProtection
     * @param TagsFormat $tagsFormat
     */
    public function __construct(
        public array $connections,
        public ?string $default = null,
        public ?MigrationConfig $migration = null,
        // Prevents destructive operations (DROP DATABASE, DROP TABLE, DROP COLUMN, TRUNCATE).
        public bool $dropProtection = false,
        public TagsFormat $tagsFormat = TagsFormat::Log,
    )
    {
    }
}
