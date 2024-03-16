<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Expressions\CurrentTimestamp;

class TimestampColumnBuilder extends ColumnBuilder
{
    /**
     * @return $this
     */
    public function currentAsDefault(): static
    {
        return $this->default(new CurrentTimestamp($this->definition));
    }
}
