<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

use Kirameki\Database\Query\Statements\TagsFormat;

class DatabaseConfig
{
    /**
     * @param array<string, ConnectionConfig> $connections
     * The connection configurations.
     * @param string|null $default
     * The default connection name.
     * @param MigrationConfig|null $migration
     * Configurations for migrations.
     * @param bool $dropProtection
     * Prevents destructive operations (DROP DATABASE, DROP TABLE, DROP COLUMN, TRUNCATE).
     * @param TagsFormat $tagsFormat
     * The format for tags used in queries.
     */
    public function __construct(
        public array $connections,
        public ?string $default = null,
        public bool $dropProtection = false,
        public TagsFormat $tagsFormat = TagsFormat::Log,
        public ?MigrationConfig $migration = null,
    )
    {
    }
}
