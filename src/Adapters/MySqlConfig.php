<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Support\TagsFormat;
use Override;

class MySqlConfig extends MySqlServerConfig implements DatabaseConfig
{
    /**
     * For why the collation is set to utf8mb4_bin by default, see
     * https://dev.mysql.com/blog-archive/sushi-beer-an-introduction-of-utf8-support-in-mysql-8-0/
     *
     * @param string|null $host
     * @param int|null $port
     * @param string|null $socket
     * @param string|null $database
     * @param string|null $username
     * @param string|null $password
     * @param bool $readonly
     * @param int $connectTimeoutSeconds
     * @param string|null $database
     * @param string|null $charset
     * @param string|null $collation
     * @param list<MySqlServerConfig>|null $replicas
     * @param array<string, mixed>|null $options
     */
    public function __construct(
        ?string $host = null,
        ?int $port = null,
        ?string $socket = null,
        ?string $username = 'root',
        ?string $password = 'root',
        bool $readonly = false,
        int $connectTimeoutSeconds = 3,
        public ?string $database = null,
        public ?string $charset = 'utf8mb4',
        public ?string $collation = 'utf8mb4_bin',
        public ?array $replicas = null,
        public ?array $options = null,
    )
    {
        parent::__construct($host, $port, $socket, $username, $password, $readonly, $connectTimeoutSeconds);
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
}
