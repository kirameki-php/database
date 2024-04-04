<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Override;

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
        public ?string $host = null,
        public ?int $port = null,
        public ?string $socket = null,
        public ?string $database = null,
        public ?string $username = 'root',
        public ?string $password = 'root',
        public ?iterable $options = null,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getAdapterName(): string
    {
        return 'mysql';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getDatabase(): string
    {
        return $this->database ?? '';
    }
}
