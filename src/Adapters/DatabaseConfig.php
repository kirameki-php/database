<?php declare(strict_types=1);

namespace Kirameki\Database\Adapters;

interface DatabaseConfig
{
    public function getAdapterName(): string;

    public function getDatabase(): string;
}
