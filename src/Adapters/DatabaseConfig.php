<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Support\TagsFormat;

interface DatabaseConfig
{
    /**
     * @return string
     */
    public function getAdapterName(): string;

    /**
     * @return string
     */
    public function getDatabase(): string;

    /**
     * @return TagsFormat
     */
    public function getTagFormat(): TagsFormat;

    /**
     * @return bool
     */
    public function isReplica(): bool;
}
