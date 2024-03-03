<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

class MySqlConfig implements DatabaseConfig
{
    /**
     * @param string|null $host
     * @param int|null $port
     * @param string|null $socket
     * @param string|null $database
     * @param string|null $username
     * @param string|null $password
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(
        public ?string $host,
        public ?int $port,
        public ?string $socket,
        public ?string $database,
        public ?string $username = 'root',
        public ?string $password = null,
        public ?iterable $options = null,
    )
    {
    }

    public function getAdapterName(): string
    {
        return 'mysql';
    }
}
