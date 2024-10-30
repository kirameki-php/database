<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Casters;

use Override;

final class StringCaster implements TypeCaster
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cast(mixed $value): string
    {
        return (string) $value;
    }
}
