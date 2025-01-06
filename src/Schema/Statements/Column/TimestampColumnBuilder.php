<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements\Column;

use Kirameki\Database\Functions\CurrentTimestamp;

class TimestampColumnBuilder extends ColumnBuilder
{
    /**
     * @return $this
     */
    public function currentAsDefault(): static
    {
        return $this->default(new CurrentTimestamp($this->definition->size ?? 6));
    }
}
