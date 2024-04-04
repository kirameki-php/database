<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

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
}
