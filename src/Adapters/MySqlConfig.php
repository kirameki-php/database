<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Support\TagsFormat;
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
     * @param bool $readonly
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(
        public ?string $host = null,
        public ?int $port = null,
        public ?string $socket = null,
        public ?string $database = null,
        public ?string $username = 'root',
        public ?string $password = 'root',
        public bool $readonly = false,
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

    /**
     * @inheritDoc
     */
    #[Override]
    public function getTagFormat(): TagsFormat
    {
        return TagsFormat::Default;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isReadOnly(): bool
    {
        return $this->readonly;
    }
}
