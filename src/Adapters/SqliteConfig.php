<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

class SqliteConfig implements DatabaseConfig
{
    /**
     * @param string $filename
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(
        public string $filename,
        public ?iterable $options = null,
    )
    {
    }

    public function getAdapterName(): string
    {
        return 'sqlite';
    }
}
