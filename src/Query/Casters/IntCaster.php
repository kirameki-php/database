<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Override;

final class IntCaster implements TypeCaster
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cast(mixed $value): int
    {
        return (int) $value;
    }
}
