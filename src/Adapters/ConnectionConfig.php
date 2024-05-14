<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

use Kirameki\Database\Query\Support\TagsFormat;

interface ConnectionConfig
{
    /**
     * @return string
     */
    public function getAdapterName(): string;

    /**
     * @return string
     */
    public function getTableSchema(): string;

    /**
     * @return TagsFormat
     */
    public function getTagFormat(): TagsFormat;

    /**
     * @return bool
     */
    public function isReplica(): bool;
}