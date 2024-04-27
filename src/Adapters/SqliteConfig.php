<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Support\TagsFormat;
use Override;

class SqliteConfig implements DatabaseConfig
{
    /**
     * @param string $filename
     * @param int $busyTimeoutSeconds
     * @param iterable<string, mixed>|null $options
     */
    public function __construct(
        public string $filename,
        public int $busyTimeoutSeconds = 30,
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
    public function getTagFormat(): TagsFormat
    {
        return TagsFormat::Default;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isReplica(): bool
    {
        return false;
    }
}
