<?php declare(strict_types=1);

namespace Kirameki\Database\Migration\ScanStrategies;

readonly class ScanStep implements ScanStrategy
{
    public function __construct(
        public int $step,
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
