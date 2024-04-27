<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

class MySqlServerConfig
{
    /**
     * @param string|null $host
     * @param int|null $port
     * @param string|null $socket
     * @param string|null $username
     * @param string|null $password
     * @param bool $readonly
     * @param int $connectTimeoutSeconds
     */
    public function __construct(
        public ?string $host = null,
        public ?int $port = null,
        public ?string $socket = null,
        public ?string $username = 'root',
        public ?string $password = 'root',
        public bool $readonly = false,
        public int $connectTimeoutSeconds = 3,
    )
    {
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->readonly;
    }
}
