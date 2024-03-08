<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

abstract class Statement
{
    /**
     * @param string $table
     */
    public function __construct(
        public readonly string $table,
    )
    {
    }
}
