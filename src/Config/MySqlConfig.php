<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

use Kirameki\Database\Transaction\IsolationLevel;
use Override;

class MySqlConfig implements ConnectionConfig
{
    /**
     * For why the collation is set to utf8mb4_bin by default, see
     * https://dev.mysql.com/blog-archive/sushi-beer-an-introduction-of-utf8-support-in-mysql-8-0/
     *
     * @param string|null $host
     * @param int|null $port
     * @param string|null $socket
     * @param string $database
     * @param string|null $username
     * @param string|null $password
     * @param string|null $charset
     * @param string|null $collation
     * @param int $connectTimeoutSeconds
     * @param int|null $transactionLockWaitTimeoutSeconds
     * @param bool $readOnly
     * @param IsolationLevel $isolationLevel
     * @param array<string, mixed>|null $serverOptions
     * @param array<string, mixed>|null $systemVariables
     */
    public function __construct(
        public ?string $host = null,
        public ?int $port = null,
        public ?string $socket = null,
        public string $database = 'main',
        public ?string $username = 'root',
        public ?string $password = null,
        public ?string $charset = 'utf8mb4',
        public ?string $collation = 'utf8mb4_bin',
        public int $connectTimeoutSeconds = 3,
        public ?int $transactionLockWaitTimeoutSeconds = 3,
        public bool $readOnly = false,
        public IsolationLevel $isolationLevel = IsolationLevel::RepeatableRead,
        public ?array $serverOptions = null,
        public ?array $systemVariables = null,
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
    public function getDatabaseName(): string
    {
        return $this->database;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getTableSchema(): string
    {
        return $this->database;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }
}
