<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

use Kirameki\Database\Transaction\Support\IsolationLevel;
use Override;

class SqliteConfig implements ConnectionConfig
{
    /**
     * @param string $filename
     * @param int $busyTimeoutSeconds
     * @param bool $readOnly
     * @param iterable<string, string>|null $pragmas
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(
        public string $filename,
        public int $busyTimeoutSeconds = 30,
        public bool $readOnly = false,
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
    public function isReadOnly(): bool
    {
        return $this->readOnly;
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
