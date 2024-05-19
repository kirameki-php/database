<?php declare(strict_types=1);

namespace Kirameki\Database\Migration\ScanStrategies;

readonly class ScanVersion implements ScanStrategy
{
    public function __construct(
        public string $version,
    )
    {
    }

    public function up(): void
    {

    }

    public function down(): void
    {

    }
}
