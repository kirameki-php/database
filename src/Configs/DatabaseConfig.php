<?php declare(strict_types=1);

namespace Kirameki\Database\Configs;

interface DatabaseConfig
{
    public function getAdapterName(): string;
}
