<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Builders;

interface Builder
{
    /**
     * @return string[]
     */
    public function build(): array;
}
