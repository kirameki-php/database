<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

use Kirameki\Database\Transaction\Support\IsolationLevel;
use Override;

class SqliteConfig implements ConnectionConfig
{
    /**
     * @param string $filename
     * @param int $busyTimeoutSeconds
     * @param iterable<string, string>|null $pragmas
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(
        public string $filename,
        public int $busyTimeoutSeconds = 30,
        public ?iterable $pragmas = null,
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
        return 'sqlite';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getTableSchema(): string
    {
        return 'sqlite';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isReplica(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getIsolationLevel(): IsolationLevel
    {
        return IsolationLevel::Serializable;
    }
}
