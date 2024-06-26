<?php declare(strict_types=1);

namespace Kirameki\Database\Config;

use Kirameki\Database\Transaction\Support\IsolationLevel;

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
     * @return bool
     */
    public function isReplica(): bool;

    /**
     * @return IsolationLevel
     */
    public function getIsolationLevel(): IsolationLevel;
}
