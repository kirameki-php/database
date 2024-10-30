<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Override;

class BoolCaster implements TypeCaster
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cast(mixed $value): bool
    {
        return (bool) $value;
    }
}
