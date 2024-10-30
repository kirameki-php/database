<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Override;

final class FloatCaster implements TypeCaster
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cast(mixed $value): float
    {
        return (float) $value;
    }
}
